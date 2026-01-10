<?php

namespace App\Jobs\Whatsapp;

use App\Services\Whatsapp\WhatsappResponseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Job para enviar mensajes masivos de WhatsApp (convocatorias).
 * 
 * Entradas:
 * - PH
 * - Reunión
 * - Template aprobado por Meta (o mensaje de texto)
 * - Lista de teléfonos
 * 
 * Reglas:
 * - Usar templates (cuando estén disponibles)
 * - Control de rate limit
 * - Reintentos automáticos
 * - Logs auditables
 */
class SendWhatsappBroadcastJob implements ShouldQueue
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
     * @param int $phId ID de la Propiedad Horizontal
     * @param int|null $reunionId ID de la reunión (opcional)
     * @param string $mensaje Texto del mensaje a enviar
     * @param array $telefonos Lista de números de teléfono
     * @param string|null $templateName Nombre del template de Meta (opcional)
     * @param array $templateParams Parámetros del template (opcional)
     */
    public function __construct(
        public int $phId,
        public ?int $reunionId,
        public string $mensaje,
        public array $telefonos,
        public ?string $templateName = null,
        public array $templateParams = []
    ) {
        // Asignar a la cola de WhatsApp
        $this->onQueue(config('whatsapp.queue', 'whatsapp'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WhatsappResponseService $responseService): void
    {
        $rateLimit = config('whatsapp.rate_limit', 80);
        $enviados = 0;
        $fallidos = 0;

        Log::info('Iniciando envío masivo de WhatsApp', [
            'ph_id' => $this->phId,
            'reunion_id' => $this->reunionId,
            'total_telefonos' => count($this->telefonos),
            'template' => $this->templateName,
        ]);

        foreach ($this->telefonos as $telefono) {
            // Control de rate limit
            $limiterKey = "whatsapp:rate_limit:{$this->phId}";
            
            if (RateLimiter::tooManyAttempts($limiterKey, $rateLimit)) {
                // Esperar antes de continuar
                $seconds = RateLimiter::availableIn($limiterKey);
                Log::info('Rate limit alcanzado, esperando', [
                    'seconds' => $seconds,
                ]);
                sleep($seconds);
            }

            RateLimiter::hit($limiterKey, 60); // Ventana de 60 segundos

            // Enviar mensaje
            if ($this->templateName) {
                $enviado = $this->enviarTemplate($responseService, $telefono);
            } else {
                $enviado = $responseService->enviarMensaje($telefono, $this->mensaje);
            }

            if ($enviado) {
                $enviados++;
            } else {
                $fallidos++;
            }

            // Pequeña pausa para evitar saturar la API
            usleep(100000); // 0.1 segundos
        }

        Log::info('Envío masivo de WhatsApp completado', [
            'ph_id' => $this->phId,
            'reunion_id' => $this->reunionId,
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'total' => count($this->telefonos),
        ]);
    }

    /**
     * Envía un mensaje usando template de Meta.
     * 
     * @param WhatsappResponseService $responseService
     * @param string $telefono
     * @return bool
     */
    protected function enviarTemplate(WhatsappResponseService $responseService, string $telefono): bool
    {
        // Por ahora, usar mensaje de texto normal
        // En el futuro, implementar envío de templates de Meta
        // Los templates requieren aprobación previa de Meta
        return $responseService->enviarMensaje($telefono, $this->mensaje);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Error en envío masivo de WhatsApp', [
            'ph_id' => $this->phId,
            'reunion_id' => $this->reunionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
