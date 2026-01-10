<?php

namespace App\Http\Controllers\Whatsapp;

use App\DTOs\WhatsappIncomingMessageDTO;
use App\Http\Controllers\Controller;
use App\Jobs\Whatsapp\ProcessWhatsappMessageJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Controlador para el webhook de WhatsApp (Meta Cloud API).
 * 
 * Responsabilidades:
 * - Validar firma de Meta
 * - Parsear payload
 * - Normalizar mensaje
 * - Enviar mensaje a Queue (Redis)
 * 
 * 🚫 Prohibido:
 * - Ejecutar lógica de negocio
 * - Acceder a DB directamente
 * 
 * El webhook solo recibe, valida y encola mensajes.
 * El procesamiento real se hace en el Job.
 */
class WhatsappWebhookController extends Controller
{
    /**
     * Verifica el webhook de Meta (GET request).
     * 
     * Meta envía un GET request con parámetros de verificación
     * cuando se configura el webhook por primera vez.
     * 
     * @param Request $request
     * @return JsonResponse|string
     */
    public function verify(Request $request)
    {
        // Verificar que WhatsApp esté habilitado
        if (!config('whatsapp.enabled', false)) {
            return response()->json([
                'error' => 'WhatsApp deshabilitado'
            ], 503);
        }

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Validar que sea una solicitud de verificación
        if ($mode !== 'subscribe') {
            Log::warning('Solicitud de verificación de webhook inválida', [
                'mode' => $mode,
            ]);
            return response()->json([
                'error' => 'Modo inválido'
            ], 400);
        }

        // Validar el token de verificación
        $verifyToken = config('whatsapp.verify_token');
        if (empty($verifyToken) || $token !== $verifyToken) {
            Log::warning('Token de verificación de webhook inválido', [
                'received_token' => $token ? '***' : 'vacío',
            ]);
            return response()->json([
                'error' => 'Token de verificación inválido'
            ], 403);
        }

        Log::info('Webhook de WhatsApp verificado exitosamente');

        // Retornar el challenge para completar la verificación
        return response($challenge, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Maneja las solicitudes del webhook de Meta.
     * 
     * Detecta automáticamente si es una solicitud de verificación (GET)
     * o un mensaje entrante (POST).
     * 
     * @param Request $request
     * @return JsonResponse|string
     */
    public function handle(Request $request)
    {
        // Verificar que WhatsApp esté habilitado
        if (!config('whatsapp.enabled', false)) {
            return response()->json([
                'error' => 'WhatsApp deshabilitado'
            ], 503);
        }

        // Si es GET, es una solicitud de verificación
        if ($request->isMethod('GET')) {
            return $this->verify($request);
        }

        // Si es POST, es un mensaje entrante
        try {
            // Validar firma de Meta
            if (!$this->validateSignature($request)) {
                Log::warning('Firma de webhook inválida', [
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'error' => 'Firma inválida'
                ], 401);
            }

            // Obtener payload
            $payload = $request->all();

            // Validar que el payload tenga la estructura esperada
            if (empty($payload['entry'])) {
                Log::warning('Payload de webhook sin entrada', [
                    'payload' => $payload,
                ]);
                return response()->json([
                    'error' => 'Payload inválido'
                ], 400);
            }

            // Procesar cada entrada (normalmente solo hay una)
            foreach ($payload['entry'] as $entry) {
                $this->processEntry($entry, $payload);
            }

            // Meta espera un 200 OK inmediatamente
            return response()->json([
                'status' => 'ok'
            ], 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('Error al procesar webhook de WhatsApp', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Error al procesar mensaje: ' . $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error inesperado en webhook de WhatsApp', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Aún así retornamos 200 para que Meta no reintente
            return response()->json([
                'status' => 'ok'
            ], 200);
        }
    }

    /**
     * Valida la firma del webhook de Meta.
     * 
     * Meta envía la firma en el header X-Hub-Signature-256
     * como un HMAC-SHA256 del payload usando el App Secret.
     * 
     * @param Request $request
     * @return bool
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (empty($signature)) {
            return false;
        }

        $secret = config('whatsapp.webhook_secret');
        
        if (empty($secret)) {
            // Si no hay secreto configurado, no validamos (solo para desarrollo)
            Log::warning('Webhook secret no configurado, saltando validación de firma');
            return true;
        }

        // Obtener el cuerpo crudo de la solicitud
        $rawBody = $request->getContent();
        
        // Calcular HMAC-SHA256
        $computedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        
        // Comparar firmas de forma segura (timing-safe)
        return hash_equals($signature, $computedSignature);
    }

    /**
     * Procesa una entrada del webhook.
     * 
     * @param array $entry Entrada del webhook
     * @param array $fullPayload Payload completo para auditoría
     * @return void
     */
    protected function processEntry(array $entry, array $fullPayload): void
    {
        $changes = $entry['changes'] ?? [];
        
        foreach ($changes as $change) {
            // Solo procesamos cambios de tipo "messages"
            if (($change['field'] ?? '') !== 'messages') {
                continue;
            }

            $value = $change['value'] ?? [];
            $messages = $value['messages'] ?? [];

            // Procesar cada mensaje
            foreach ($messages as $message) {
                try {
                    // Crear DTO desde el mensaje individual de Meta
                    $dto = WhatsappIncomingMessageDTO::fromMetaMessage($message, $fullPayload);

                    Log::info('Mensaje de WhatsApp recibido y normalizado', [
                        'from' => $dto->from,
                        'message' => $dto->message,
                        'message_id' => $dto->messageId,
                    ]);

                    // Enviar a la cola para procesamiento asíncrono
                    ProcessWhatsappMessageJob::dispatch($dto);

                } catch (\InvalidArgumentException $e) {
                    Log::warning('Error al normalizar mensaje de WhatsApp', [
                        'error' => $e->getMessage(),
                        'message' => $message,
                    ]);
                    // Continuar con el siguiente mensaje
                    continue;
                }
            }
        }
    }
}
