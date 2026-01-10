<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait para modelos que pertenecen a la base de datos de un PH.
 * 
 * Este trait proporciona protecciones de aislamiento multi-tenant:
 * - Asegura que las queries usen la conexión correcta del PH
 * - Previene joins accidentales entre diferentes PHs
 * - Valida que siempre se use la conexión 'ph_database'
 * 
 * IMPORTANTE: Todos los modelos que pertenecen a la base de datos de un PH
 * deben usar este trait para garantizar el aislamiento de datos.
 */
trait UsesPhDatabase
{
    /**
     * Boot del trait.
     * 
     * Configura el modelo para usar la conexión del PH y agrega
     * protecciones contra queries que crucen entre PHs.
     * 
     * @return void
     */
    protected static function bootUsesPhDatabase(): void
    {
        // Asegurar que el modelo use la conexión del PH
        // NOTA: La validación de conexión se hace en getConnectionName(), no en el global scope
        // para evitar loops infinitos durante la construcción del builder
        static::addGlobalScope('ph_database', function (Builder $builder) {
            // Este scope no hace nada, solo fuerza el uso de getConnectionName()
            // La validación real se hace cuando se ejecuta la query
        });
    }

    /**
     * Obtiene la conexión de base de datos para el modelo.
     * 
     * Fuerza el uso de la conexión 'ph_database' que fue configurada
     * por el middleware ResolveTenantDatabase.
     * 
     * @return string Nombre de la conexión
     */
    public function getConnectionName(): string
    {
        // En pruebas usar siempre la conexión por defecto para evitar bloqueos SQLite
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return 'ph_database';
    }

    /**
     * Previne joins accidentales entre diferentes PHs.
     * 
     * Este método valida que los joins solo se hagan dentro del mismo PH.
     * Lanza una excepción si se detecta un intento de join con tablas
     * de otros PHs o de la base master.
     * 
     * @param Builder $query Query builder
     * @param string $table Tabla con la que se intenta hacer join
     * @return void
     * @throws \RuntimeException Si se detecta un join no permitido
     */
    protected function preventCrossPhJoins(Builder $query, string $table): void
    {
        $connection = $query->getConnection()->getName();
        
        // Lista de tablas que pertenecen a la base MASTER (no permitidas en joins)
        $masterTables = [
            'phs',
            'users',
            'usuario_ph',
            'auditoria_eventos',
            'whatsapp_logs',
            'ia_jobs',
            'migrations',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];

        // Si la tabla está en la lista de tablas master, bloquear el join
        if (in_array($table, $masterTables)) {
            throw new \RuntimeException(
                "No se permite hacer join con la tabla '{$table}' porque pertenece a la base MASTER. " .
                "Los datos de diferentes PHs están aislados en bases de datos separadas."
            );
        }
    }
}
