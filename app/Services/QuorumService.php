<?php

namespace App\Services;

use App\Models\Asistentes\Asistente;
use App\Models\Inmuebles\Inmueble;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Servicio para calcular y gestionar el quórum.
 * 
 * El quórum es un modelo DERIVADO (NO es una tabla en la base de datos).
 * Se calcula a partir de:
 * - Total de inmuebles registrados (con asistentes)
 * - Suma de coeficientes de esos inmuebles
 * 
 * Persistencia: Redis (con fallback a DB si Redis no está disponible)
 * 
 * IMPORTANTE: El quórum nunca es editable directamente, solo se recalcula.
 */
class QuorumService
{
    /**
     * Clave base para almacenar datos de quórum en Redis.
     */
    protected const REDIS_KEY_PREFIX = 'quorum:ph:';

    /**
     * TTL por defecto para los datos de quórum en Redis (1 hora).
     */
    protected const DEFAULT_TTL = 3600;

    /**
     * Calcula el quórum actual.
     * 
     * El quórum se calcula sumando los coeficientes de todos los inmuebles
     * que tienen asistentes registrados en la reunión actual.
     * 
     * @param int|null $reunionId ID de la reunión (opcional, si no se proporciona calcula para todas)
     * @return array Datos del quórum: ['total_inmuebles', 'suma_coeficientes', 'porcentaje']
     */
    public function calcular(int $reunionId = null): array
    {
        // Obtener todos los inmuebles que tienen asistentes registrados
        $inmueblesConAsistentes = Inmueble::query()
            ->where('activo', true)
            ->whereHas('asistentes')
            ->get();

        $totalInmuebles = $inmueblesConAsistentes->count();
        $sumaCoeficientes = $inmueblesConAsistentes->sum('coeficiente');

        // Obtener total de inmuebles activos para calcular porcentaje
        $totalInmueblesActivos = Inmueble::where('activo', true)->count();
        $sumaTotalCoeficientes = Inmueble::where('activo', true)->sum('coeficiente');

        $porcentaje = $sumaTotalCoeficientes > 0 
            ? ($sumaCoeficientes / $sumaTotalCoeficientes) * 100 
            : 0;

        return [
            'total_inmuebles' => $totalInmuebles,
            'suma_coeficientes' => round($sumaCoeficientes, 2),
            'porcentaje' => round($porcentaje, 2),
            'total_inmuebles_activos' => $totalInmueblesActivos,
            'suma_total_coeficientes' => round($sumaTotalCoeficientes, 2),
            'reunion_id' => $reunionId,
            'calculado_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtiene el quórum desde Redis o lo calcula si no existe.
     * 
     * @param int|null $reunionId ID de la reunión
     * @return array Datos del quórum
     */
    public function obtener(int $reunionId = null): array
    {
        $cacheKey = $this->getCacheKey($reunionId);

        // Intentar obtener desde Redis
        try {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return json_decode($cached, true);
            }
        } catch (\Exception $e) {
            // Si Redis falla, continuar con cálculo
        }

        // Si no está en Redis, calcular y guardar
        $quorum = $this->calcular($reunionId);
        $this->guardar($quorum, $reunionId);

        return $quorum;
    }

    /**
     * Guarda el quórum en Redis.
     * 
     * @param array $quorum Datos del quórum
     * @param int|null $reunionId ID de la reunión
     * @return bool
     */
    public function guardar(array $quorum, int $reunionId = null): bool
    {
        $cacheKey = $this->getCacheKey($reunionId);

        try {
            Redis::setex($cacheKey, self::DEFAULT_TTL, json_encode($quorum));
            return true;
        } catch (\Exception $e) {
            // Si Redis falla, usar Cache de Laravel como fallback
            Cache::put($cacheKey, $quorum, self::DEFAULT_TTL);
            return false;
        }
    }

    /**
     * Recalcula el quórum y lo guarda.
     * 
     * Útil para forzar un recálculo bajo demanda.
     * Emite evento QuorumUpdated si se proporciona reunionId.
     * 
     * @param int|null $reunionId ID de la reunión
     * @return array Datos del quórum recalculado
     */
    public function recalcular(int $reunionId = null): array
    {
        $quorum = $this->calcular($reunionId);
        $this->guardar($quorum, $reunionId);
        
        // Emitir evento de broadcasting si hay reunionId
        if ($reunionId) {
            event(new \App\Events\QuorumUpdated($reunionId, $quorum));
        }
        
        return $quorum;
    }

    /**
     * Limpia el cache del quórum.
     * 
     * @param int|null $reunionId ID de la reunión
     * @return void
     */
    public function limpiarCache(int $reunionId = null): void
    {
        $cacheKey = $this->getCacheKey($reunionId);

        try {
            Redis::del($cacheKey);
        } catch (\Exception $e) {
            // Si Redis falla, usar Cache de Laravel
            Cache::forget($cacheKey);
        }
    }

    /**
     * Verifica si se alcanzó el quórum mínimo.
     * 
     * El quórum mínimo típicamente es el 50% + 1 de los coeficientes.
     * 
     * @param float $porcentajeMinimo Porcentaje mínimo requerido (default: 50.01)
     * @param int|null $reunionId ID de la reunión
     * @return bool
     */
    public function seAlcanzoQuorum(float $porcentajeMinimo = 50.01, int $reunionId = null): bool
    {
        $quorum = $this->obtener($reunionId);
        return $quorum['porcentaje'] >= $porcentajeMinimo;
    }

    /**
     * Obtiene la clave de cache para Redis.
     * 
     * @param int|null $reunionId ID de la reunión
     * @return string
     */
    protected function getCacheKey(?int $reunionId): string
    {
        $phId = request()->attributes->get('ph')?->id ?? 'default';
        $reunion = $reunionId ? ":reunion:{$reunionId}" : '';
        return self::REDIS_KEY_PREFIX . $phId . $reunion;
    }
}
