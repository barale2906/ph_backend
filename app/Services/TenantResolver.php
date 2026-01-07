<?php

namespace App\Services;

use App\Models\Ph;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantResolver
{
    /**
     * Resuelve el PH por NIT y configura la conexión de base de datos
     *
     * @param string $nit
     * @return Ph|null
     * @throws \Exception
     */
    public function resolve(string $nit): ?Ph
    {
        // Cachear la resolución por 1 hora
        $cacheKey = "ph_resolver:{$nit}";

        return Cache::remember($cacheKey, 3600, function () use ($nit) {
            $ph = Ph::where('nit', $nit)
                ->where('estado', 'activo')
                ->first();

            if (!$ph) {
                return null;
            }

            // Configurar conexión dinámica
            $this->setDatabaseConnection($ph->db_name);

            return $ph;
        });
    }

    /**
     * Configura la conexión de base de datos para el PH
     *
     * @param string $dbName
     * @return void
     */
    protected function setDatabaseConnection(string $dbName): void
    {
        $config = Config::get('database.connections.pgsql');
        $config['database'] = $dbName;

        Config::set("database.connections.ph_database", $config);
        DB::purge('ph_database');
        DB::setDefaultConnection('ph_database');
    }

    /**
     * Limpia el cache de resolución de PH
     *
     * @param string $nit
     * @return void
     */
    public function clearCache(string $nit): void
    {
        Cache::forget("ph_resolver:{$nit}");
    }
}

