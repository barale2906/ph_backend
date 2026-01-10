<?php

namespace App\Services;

use App\Models\Ph;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

/**
 * Servicio para gestionar bases de datos de Propiedades Horizontales.
 * 
 * Este servicio se encarga de:
 * - Crear bases de datos para PHs
 * - Ejecutar migraciones en bases de datos de PHs
 * - Eliminar bases de datos de PHs
 */
class PhDatabaseService
{
    /**
     * Crea la base de datos para un PH y ejecuta las migraciones.
     * 
     * @param Ph $ph Instancia del PH
     * @return bool True si se creó exitosamente, false en caso contrario
     * @throws \Exception Si hay un error al crear la base de datos
     */
    public function crearBaseDatos(Ph $ph): bool
    {
        try {
            // Conectar a la base de datos postgres (por defecto) para crear la nueva base
            $config = Config::get('database.connections.pgsql');
            
            // Conectar sin especificar base de datos (conecta a 'postgres')
            $connection = new \PDO(
                "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
                $config['username'],
                $config['password']
            );
            
            // Crear la base de datos
            $dbName = $ph->db_name;
            $connection->exec("CREATE DATABASE \"{$dbName}\" WITH OWNER = {$config['username']} ENCODING = 'UTF8'");
            
            // Ejecutar migraciones en la nueva base de datos
            $this->ejecutarMigraciones($ph);
            
            return true;
        } catch (\PDOException $e) {
            // Si la base de datos ya existe, no es un error crítico
            if (str_contains($e->getMessage(), 'already exists')) {
                // Verificar si las migraciones están ejecutadas
                $this->ejecutarMigraciones($ph);
                return true;
            }
            
            throw new \Exception("Error al crear la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta las migraciones en la base de datos del PH.
     * 
     * @param Ph $ph Instancia del PH
     * @return void
     */
    public function ejecutarMigraciones(Ph $ph): void
    {
        // Configurar conexión temporal para las migraciones
        $config = Config::get('database.connections.pgsql');
        $config['database'] = $ph->db_name;
        
        Config::set('database.connections.ph_temp_migration', $config);
        DB::purge('ph_temp_migration');
        
        // Ejecutar migraciones usando la conexión temporal
        Artisan::call('migrate', [
            '--database' => 'ph_temp_migration',
            '--path' => 'database/migrations',
            '--force' => true,
        ], outputBuffer: null);
        
        // Limpiar conexión temporal
        DB::purge('ph_temp_migration');
        Config::forget('database.connections.ph_temp_migration');
    }

    /**
     * Elimina la base de datos de un PH.
     * 
     * ⚠️ ADVERTENCIA: Esta operación es destructiva y no se puede deshacer.
     * 
     * @param Ph $ph Instancia del PH
     * @return bool True si se eliminó exitosamente
     * @throws \Exception Si hay un error al eliminar la base de datos
     */
    public function eliminarBaseDatos(Ph $ph): bool
    {
        try {
            // Conectar a la base de datos postgres para eliminar la base
            $config = Config::get('database.connections.pgsql');
            
            $connection = new \PDO(
                "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
                $config['username'],
                $config['password']
            );
            
            // Terminar todas las conexiones a la base de datos antes de eliminarla
            $dbName = $ph->db_name;
            $connection->exec("
                SELECT pg_terminate_backend(pg_stat_activity.pid)
                FROM pg_stat_activity
                WHERE pg_stat_activity.datname = '{$dbName}'
                AND pid <> pg_backend_pid()
            ");
            
            // Eliminar la base de datos
            $connection->exec("DROP DATABASE IF EXISTS \"{$dbName}\"");
            
            return true;
        } catch (\PDOException $e) {
            throw new \Exception("Error al eliminar la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Verifica si la base de datos del PH existe.
     * 
     * @param Ph $ph Instancia del PH
     * @return bool True si existe, false en caso contrario
     */
    public function existeBaseDatos(Ph $ph): bool
    {
        try {
            $config = Config::get('database.connections.pgsql');
            
            $connection = new \PDO(
                "pgsql:host={$config['host']};port={$config['port']};dbname=postgres",
                $config['username'],
                $config['password']
            );
            
            $stmt = $connection->prepare("
                SELECT 1 FROM pg_database WHERE datname = ?
            ");
            $stmt->execute([$ph->db_name]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
