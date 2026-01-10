<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar respuestas a usuarios de WhatsApp.
 * 
 * Funciones:
 * - Confirmación de voto
 * - Error claro (votación cerrada, fuera de tiempo)
 * - Notificación de cierre
 * 
 * Reglas:
 * - Mensajes cortos
 * - Sin ambigüedad
 * - Sin estado local
 */
class WhatsappResponseService
{
    /**
     * Envía un mensaje de texto a un número de WhatsApp.
     * 
     * @param string $telefono Número de teléfono (formato internacional sin +)
     * @param string $mensaje Texto del mensaje
     * @return bool True si se envió exitosamente, false en caso contrario
     */
    public function enviarMensaje(string $telefono, string $mensaje): bool
    {
        // Verificar que WhatsApp esté habilitado
        if (!config('whatsapp.enabled', false)) {
            Log::warning('Intento de enviar mensaje de WhatsApp con WhatsApp deshabilitado', [
                'telefono' => $telefono,
            ]);
            return false;
        }

        $token = config('whatsapp.token');
        $phoneId = config('whatsapp.phone_id');
        $apiBaseUrl = config('whatsapp.api_base_url');
        $timeout = config('whatsapp.timeout', 30);

        if (empty($token) || empty($phoneId)) {
            Log::error('Configuración de WhatsApp incompleta para enviar mensaje', [
                'telefono' => $telefono,
            ]);
            return false;
        }

        // Limitar longitud del mensaje (Meta permite hasta 4096 caracteres)
        $mensaje = mb_substr($mensaje, 0, 4096);

        try {
            $url = "{$apiBaseUrl}/{$phoneId}/messages";

            $response = Http::timeout($timeout)
                ->withToken($token)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $telefono,
                    'type' => 'text',
                    'text' => [
                        'body' => $mensaje,
                    ],
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Mensaje de WhatsApp enviado exitosamente', [
                    'telefono' => $telefono,
                    'message_id' => $responseData['messages'][0]['id'] ?? null,
                ]);

                return true;
            }

            Log::error('Error al enviar mensaje de WhatsApp', [
                'telefono' => $telefono,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Excepción al enviar mensaje de WhatsApp', [
                'telefono' => $telefono,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Envía un mensaje de confirmación de voto.
     * 
     * @param string $telefono
     * @param string $opcion Opción votada
     * @return bool
     */
    public function enviarConfirmacionVoto(string $telefono, string $opcion): bool
    {
        $mensaje = "✅ Voto registrado: {$opcion}";
        return $this->enviarMensaje($telefono, $mensaje);
    }

    /**
     * Envía un mensaje de error cuando la votación está cerrada.
     * 
     * @param string $telefono
     * @return bool
     */
    public function enviarErrorVotacionCerrada(string $telefono): bool
    {
        $mensaje = "❌ La votación ya está cerrada. No se puede votar en este momento.";
        return $this->enviarMensaje($telefono, $mensaje);
    }

    /**
     * Envía un mensaje de error cuando no hay votación abierta.
     * 
     * @param string $telefono
     * @return bool
     */
    public function enviarErrorNoHayVotacion(string $telefono): bool
    {
        $mensaje = "ℹ️ No hay ninguna votación abierta en este momento.";
        return $this->enviarMensaje($telefono, $mensaje);
    }

    /**
     * Envía un mensaje de error genérico.
     * 
     * @param string $telefono
     * @param string $mensajeError
     * @return bool
     */
    public function enviarError(string $telefono, string $mensajeError): bool
    {
        $mensaje = "❌ Error: {$mensajeError}";
        return $this->enviarMensaje($telefono, $mensaje);
    }
}
