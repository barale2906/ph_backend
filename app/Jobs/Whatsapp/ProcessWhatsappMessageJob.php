<?php

namespace App\Jobs\Whatsapp;

use App\DTOs\WhatsappIncomingMessageDTO;
use App\Services\Whatsapp\WhatsappCommandRouter;
use App\Services\Whatsapp\WhatsappResponseService;
use App\Services\Whatsapp\WhatsappSecurityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para procesar mensajes entrantes de WhatsApp.
 * 
 * Este job se ejecuta en la cola después de que el webhook
 * recibe y normaliza un mensaje de WhatsApp.
 * 
 * Responsabilidades:
 * - Resolver el PH del asistente
 * - Llamar al router de comandos
 * - Enviar respuesta al usuario
 * 
 * La lógica de negocio (votaciones, quórum, etc.) se ejecuta
 * en servicios del core, no aquí.
 */
class ProcessWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos máximos.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tiempo de espera antes de reintentar (segundos).
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param WhatsappIncomingMessageDTO $message DTO del mensaje normalizado
     */
    public function __construct(
        public WhatsappIncomingMessageDTO $message
    ) {
        // Asignar a la cola de WhatsApp
        $this->onQueue(config('whatsapp.queue', 'whatsapp'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        WhatsappCommandRouter $commandRouter,
        WhatsappResponseService $responseService,
        WhatsappSecurityService $securityService
    ): void {
        Log::info('Procesando mensaje de WhatsApp', [
            'from' => $this->message->from,
            'message' => $this->message->message,
            'message_id' => $this->message->messageId,
            'timestamp' => $this->message->timestamp,
        ]);

        try {
            // Proteccion contra replay: message_id único con TTL
            if (!$securityService->registrarMessageId($this->message->messageId)) {
                Log::warning('Mensaje duplicado detectado (replay)', [
                    'message_id' => $this->message->messageId,
                    'telefono' => $this->message->from,
                ]);

                $securityService->auditar(
                    phId: null,
                    reunionId: null,
                    telefono: $this->message->from,
                    tipo: 'mensaje_recibido',
                    datos: [
                        'message_id' => $this->message->messageId,
                        'estado' => 'duplicado',
                        'motivo' => 'Replay detectado',
                    ]
                );
                return;
            }

            // Verificar permisos de seguridad (rate limit, flood)
            $permiso = $securityService->verificarPermiso($this->message->from);
            
            if (!$permiso['allowed']) {
                Log::warning('Mensaje bloqueado por seguridad', [
                    'telefono' => $this->message->from,
                    'reason' => $permiso['reason'],
                ]);

                // Enviar mensaje de bloqueo al usuario
                $mensajeBloqueo = "⛔ {$permiso['reason']}";
                if ($permiso['retry_after']) {
                    $minutos = ceil($permiso['retry_after'] / 60);
                    $mensajeBloqueo .= " Intenta nuevamente en {$minutos} minuto(s).";
                }
                $responseService->enviarMensaje($this->message->from, $mensajeBloqueo);

                $securityService->auditar(
                    phId: null,
                    reunionId: null,
                    telefono: $this->message->from,
                    tipo: 'mensaje_recibido',
                    datos: [
                        'message_id' => $this->message->messageId,
                        'estado' => 'rechazado',
                        'motivo' => $permiso['reason'],
                    ]
                );
                return;
            }

            // Registrar mensaje para control de rate limit
            $securityService->registrarMensaje($this->message->from);

            // Resolver el PH del asistente
            $ph = $this->resolverPhDelAsistente($this->message->from);

            if (!$ph) {
                Log::warning('No se encontró PH para el asistente', [
                    'telefono' => $this->message->from,
                ]);
                // Enviar mensaje de error al usuario
                $responseService->enviarMensaje(
                    $this->message->from,
                    'No estás registrado en ninguna Propiedad Horizontal. Contacta al administrador.'
                );

                $securityService->auditar(
                    phId: null,
                    reunionId: null,
                    telefono: $this->message->from,
                    tipo: 'mensaje_recibido',
                    datos: [
                        'message_id' => $this->message->messageId,
                        'estado' => 'rechazado',
                        'motivo' => 'telefono_no_registrado',
                    ]
                );
                return;
            }

            // Configurar conexión del PH usando TenantResolver
            $tenantResolver = app(\App\Services\TenantResolver::class);
            $tenantResolver->resolve($ph->nit);

            // Procesar comando
            $resultado = $commandRouter->process($this->message);

            // Enviar respuesta al usuario
            $responseService->enviarMensaje(
                $this->message->from,
                $resultado->message
            );

            // Auditoría
            $reunionId = $resultado->data['reunion_id'] ?? null;
            $securityService->auditar(
                phId: $ph->id,
                reunionId: $reunionId,
                telefono: $this->message->from,
                tipo: $resultado->isSuccess() ? 'comando_procesado' : 'error_comando',
                datos: [
                    'mensaje' => $this->message->message,
                    'resultado' => $resultado->isSuccess(),
                    'message_id' => $this->message->messageId,
                    'estado' => $resultado->isSuccess() ? 'procesado' : 'error',
                ]
            );

            Log::info('Mensaje de WhatsApp procesado exitosamente', [
                'telefono' => $this->message->from,
                'success' => $resultado->isSuccess(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error al procesar mensaje de WhatsApp', [
                'telefono' => $this->message->from,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Intentar enviar mensaje de error al usuario
            try {
                $responseService->enviarMensaje(
                    $this->message->from,
                    'Ocurrió un error al procesar tu mensaje. Por favor intenta más tarde.'
                );
            } catch (\Exception $e2) {
                Log::error('Error al enviar mensaje de error al usuario', [
                    'error' => $e2->getMessage(),
                ]);
            }

            // Re-lanzar la excepción para que el job falle y se reintente
            throw $e;
        }
    }

    /**
     * Resuelve el PH al que pertenece un asistente buscando por teléfono.
     * 
     * @param string $telefono
     * @return \App\Models\Ph|null
     */
    protected function resolverPhDelAsistente(string $telefono): ?\App\Models\Ph
    {
        // Restaurar conexión master primero
        \Illuminate\Support\Facades\DB::setDefaultConnection(config('database.default'));

        // Buscar en todas las PHs activas
        $phs = \App\Models\Ph::where('estado', 'activo')->get();

        foreach ($phs as $ph) {
            // Configurar conexión temporal para esta PH usando TenantResolver
            $tenantResolver = app(\App\Services\TenantResolver::class);
            
            // Usar reflexión para acceder al método protegido setDatabaseConnection
            // O mejor, usar el método resolve que ya hace todo
            try {
                $phResuelto = $tenantResolver->resolve($ph->nit);
                
                if ($phResuelto) {
                    // Buscar asistente en esta PH
                    $asistente = \App\Models\Asistentes\Asistente::where('telefono', $telefono)->first();

                    if ($asistente) {
                        return $ph;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al resolver PH para buscar asistente', [
                    'ph_id' => $ph->id,
                    'nit' => $ph->nit,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }
}
