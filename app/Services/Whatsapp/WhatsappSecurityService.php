<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Servicio de seguridad para WhatsApp.
 * 
 * Implementa:
 * - Validación del origen del webhook (ya implementada en el controlador)
 * - Rate limit por número
 * - Bloqueo por flood
 * - Auditoría por PH y reunión
 */
class WhatsappSecurityService
{
    /**
     * Límite de mensajes por minuto por número.
     */
    protected const RATE_LIMIT_PER_MINUTE = 10;

    /**
     * Límite de mensajes por hora por número.
     */
    protected const RATE_LIMIT_PER_HOUR = 50;

    /**
     * Número de mensajes que activan el bloqueo por flood.
     */
    protected const FLOOD_THRESHOLD = 20;

    /**
     * Duración del bloqueo por flood (segundos).
     */
    protected const FLOOD_BLOCK_DURATION = 3600; // 1 hora

    /**
     * Verifica si un número puede enviar mensajes (rate limit y flood).
     * 
     * @param string $telefono Número de teléfono
     * @return array ['allowed' => bool, 'reason' => string|null, 'retry_after' => int|null]
     */
    public function verificarPermiso(string $telefono): array
    {
        // Verificar bloqueo por flood
        $floodKey = "whatsapp:flood:{$telefono}";
        if (Cache::has($floodKey)) {
            $retryAfter = Cache::get("{$floodKey}:retry_after", self::FLOOD_BLOCK_DURATION);
            return [
                'allowed' => false,
                'reason' => 'Bloqueado por flood. Demasiados mensajes en poco tiempo.',
                'retry_after' => $retryAfter,
            ];
        }

        // Verificar rate limit por minuto
        $minuteKey = "whatsapp:rate_limit:minute:{$telefono}";
        if (RateLimiter::tooManyAttempts($minuteKey, self::RATE_LIMIT_PER_MINUTE)) {
            $retryAfter = RateLimiter::availableIn($minuteKey);
            return [
                'allowed' => false,
                'reason' => 'Rate limit excedido. Demasiados mensajes por minuto.',
                'retry_after' => $retryAfter,
            ];
        }

        // Verificar rate limit por hora
        $hourKey = "whatsapp:rate_limit:hour:{$telefono}";
        if (RateLimiter::tooManyAttempts($hourKey, self::RATE_LIMIT_PER_HOUR)) {
            $retryAfter = RateLimiter::availableIn($hourKey);
            return [
                'allowed' => false,
                'reason' => 'Rate limit excedido. Demasiados mensajes por hora.',
                'retry_after' => $retryAfter,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'retry_after' => null,
        ];
    }

    /**
     * Registra un mensaje recibido para control de rate limit y flood.
     * 
     * @param string $telefono
     * @return void
     */
    public function registrarMensaje(string $telefono): void
    {
        // Incrementar contadores de rate limit
        $minuteKey = "whatsapp:rate_limit:minute:{$telefono}";
        $hourKey = "whatsapp:rate_limit:hour:{$telefono}";

        RateLimiter::hit($minuteKey, 60); // Ventana de 60 segundos
        RateLimiter::hit($hourKey, 3600); // Ventana de 3600 segundos

        // Verificar flood
        $floodKey = "whatsapp:flood:{$telefono}";
        $count = Cache::increment("whatsapp:flood:count:{$telefono}", 1);
        
        if ($count === 1) {
            // Primera vez, establecer TTL
            Cache::put("whatsapp:flood:count:{$telefono}", 1, 60); // Contar en ventana de 60 segundos
        }

        // Si supera el umbral, bloquear
        if ($count >= self::FLOOD_THRESHOLD) {
            $this->bloquearPorFlood($telefono);
            
            Log::warning('Número bloqueado por flood', [
                'telefono' => $telefono,
                'count' => $count,
            ]);
        }
    }

    /**
     * Bloquea un número por flood.
     * 
     * @param string $telefono
     * @return void
     */
    protected function bloquearPorFlood(string $telefono): void
    {
        $floodKey = "whatsapp:flood:{$telefono}";
        Cache::put($floodKey, true, self::FLOOD_BLOCK_DURATION);
        Cache::put("{$floodKey}:retry_after", self::FLOOD_BLOCK_DURATION, self::FLOOD_BLOCK_DURATION);
    }

    /**
     * Desbloquea un número (útil para administración).
     * 
     * @param string $telefono
     * @return void
     */
    public function desbloquear(string $telefono): void
    {
        $floodKey = "whatsapp:flood:{$telefono}";
        Cache::forget($floodKey);
        Cache::forget("{$floodKey}:retry_after");
        Cache::forget("whatsapp:flood:count:{$telefono}");
        
        // Limpiar rate limiters
        RateLimiter::clear("whatsapp:rate_limit:minute:{$telefono}");
        RateLimiter::clear("whatsapp:rate_limit:hour:{$telefono}");
    }

    /**
     * Registra un evento de auditoría.
     * 
     * @param int|null $phId ID de la PH
     * @param int|null $reunionId ID de la reunión
     * @param string $telefono
     * @param string $tipo Tipo de evento (mensaje_recibido, mensaje_enviado, voto_registrado, etc.)
     * @param array $datos Datos adicionales
     * @return void
     */
    public function auditar(int $phId = null, int $reunionId = null, string $telefono = '', string $tipo = '', array $datos = []): void
    {
        Log::info('Auditoría WhatsApp', [
            'ph_id' => $phId,
            'reunion_id' => $reunionId,
            'telefono' => $telefono,
            'tipo' => $tipo,
            'datos' => $datos,
            'timestamp' => now()->toIso8601String(),
        ]);

        // TODO: En el futuro, guardar en tabla de auditoría específica de WhatsApp
        // Por ahora, solo se registra en logs
    }
}
