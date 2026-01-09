<?php

namespace App\Services;

use App\Models\Ph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para resolver Propiedades Horizontales (PHs) y configurar conexiones de base de datos.
 * 
 * Este servicio es responsable de:
 * - Resolver un PH por su NIT
 * - Configurar la conexión de base de datos dinámica para el PH
 * - Cachear las resoluciones para mejorar el rendimiento
 * 
 * IMPORTANTE: Este servicio NO debe contener lógica de negocio.
 * Solo se encarga de la resolución y configuración de conexiones.
 */
class TenantResolver
{
    /**
     * Resuelve el PH por NIT y configura la conexión de base de datos dinámicamente.
     * 
     * El PH se busca en la base de datos MASTER usando la conexión por defecto.
     * Una vez encontrado, se configura la conexión específica del PH.
     * 
     * @param string $nit NIT de la Propiedad Horizontal
     * @return Ph|null Instancia del PH si se encuentra y está activo, null en caso contrario
     * @throws \Exception Si hay un error al configurar la conexión
     */
    public function resolve(string $nit): ?Ph
    {
        // Cachear la resolución por 1 hora para mejorar rendimiento
        $cacheKey = "ph_resolver:{$nit}";

        return Cache::remember($cacheKey, 3600, function () use ($nit) {
            // IMPORTANTE: Usar conexión master (por defecto) para buscar el PH
            // Asegurar que estamos en la conexión master antes de buscar
            DB::setDefaultConnection(Config::get('database.default'));
            
            $ph = Ph::on(Config::get('database.default'))
                ->where('nit', $nit)
                ->where('estado', 'activo')
                ->first();

            if (!$ph) {
                return null;
            }

            // Configurar conexión dinámica para el PH
            $this->setDatabaseConnection($ph->db_name);

            return $ph;
        });
    }

    /**
     * Configura la conexión de base de datos dinámica para el PH.
     * 
     * Crea o actualiza la conexión 'ph_database' con el nombre de base de datos
     * específico del PH. Esta conexión será usada para todas las operaciones
     * relacionadas con ese PH.
     * 
     * @param string $dbName Nombre de la base de datos del PH
     * @return void
     * @throws \Exception Si hay un error al configurar la conexión
     */
    protected function setDatabaseConnection(string $dbName): void
    {
        // Obtener configuración base de PostgreSQL
        $baseConfig = Config::get('database.connections.pgsql');
        
        // Crear configuración específica para este PH
        $config = array_merge($baseConfig, [
            'database' => $dbName,
        ]);

        // Configurar la conexión dinámica
        Config::set("database.connections.ph_database", $config);
        
        // Limpiar cualquier conexión existente para forzar recreación
        DB::purge('ph_database');
        
        // Establecer como conexión por defecto para este request
        DB::setDefaultConnection('ph_database');
    }

    /**
     * Limpia el cache de resolución de PH.
     * 
     * Útil cuando se actualiza información de un PH y se necesita
     * forzar una nueva resolución.
     * 
     * @param string $nit NIT del PH cuyo cache se desea limpiar
     * @return void
     */
    public function clearCache(string $nit): void
    {
        Cache::forget("ph_resolver:{$nit}");
    }

    /**
     * Limpia todo el cache de resoluciones de PH.
     * 
     * Útil para limpieza general o cuando hay cambios masivos.
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        // Nota: En producción, considerar usar tags de cache para mejor control
        Cache::flush();
    }
}

