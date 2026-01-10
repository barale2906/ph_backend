<?php

namespace App\Console\Commands;

use App\Models\Ph;
use App\Services\PhDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Comando Artisan para crear una Propiedad Horizontal completa.
 * 
 * Este comando crea:
 * - El registro del PH en la base de datos MASTER
 * - La base de datos física del PH
 * - Ejecuta todas las migraciones en la nueva base de datos
 * 
 * Uso:
 * php artisan ph:crear --nit=900123456 --nombre="PH Ejemplo" --db_name=ph_ejemplo_900123456
 */
class CrearPhCompleto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ph:crear 
                            {--nit= : NIT de la Propiedad Horizontal}
                            {--nombre= : Nombre de la PH}
                            {--db_name= : Nombre de la base de datos (opcional, se genera automáticamente si no se proporciona)}
                            {--estado=activo : Estado inicial (activo/inactivo)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una Propiedad Horizontal completa (registro + base de datos + migraciones)';

    /**
     * Servicio para gestionar bases de datos de PH.
     * 
     * @var PhDatabaseService
     */
    protected PhDatabaseService $phDatabaseService;

    /**
     * Constructor.
     * 
     * @param PhDatabaseService $phDatabaseService
     */
    public function __construct(PhDatabaseService $phDatabaseService)
    {
        parent::__construct();
        $this->phDatabaseService = $phDatabaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Creando Propiedad Horizontal completa...');
        $this->newLine();

        // Obtener datos del comando o solicitar interactivamente
        $nit = $this->option('nit') ?: $this->ask('NIT de la Propiedad Horizontal');
        $nombre = $this->option('nombre') ?: $this->ask('Nombre de la PH');
        $dbName = $this->option('db_name') ?: $this->generarNombreBaseDatos($nit, $nombre);
        $estado = $this->option('estado');

        // Validar datos
        $validator = Validator::make([
            'nit' => $nit,
            'nombre' => $nombre,
            'db_name' => $dbName,
            'estado' => $estado,
        ], [
            'nit' => 'required|string|unique:phs,nit',
            'nombre' => 'required|string|max:255',
            'db_name' => 'required|string|regex:/^[a-z0-9_]+$/|unique:phs,db_name',
            'estado' => 'in:activo,inactivo',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Errores de validación:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  - {$error}");
            }
            return Command::FAILURE;
        }

        // Mostrar resumen
        $this->info('📋 Resumen:');
        $this->line("  NIT: {$nit}");
        $this->line("  Nombre: {$nombre}");
        $this->line("  Base de datos: {$dbName}");
        $this->line("  Estado: {$estado}");
        $this->newLine();

        if (!$this->confirm('¿Desea continuar?', true)) {
            $this->warn('Operación cancelada.');
            return Command::FAILURE;
        }

        try {
            // Paso 1: Crear registro del PH
            $this->info('📝 Creando registro del PH...');
            $ph = Ph::create([
                'nit' => $nit,
                'nombre' => $nombre,
                'db_name' => $dbName,
                'estado' => $estado,
            ]);
            $this->info("✅ PH creado con ID: {$ph->id}");

            // Paso 2: Crear base de datos
            $this->info('🗄️  Creando base de datos...');
            $this->phDatabaseService->crearBaseDatos($ph);
            $this->info("✅ Base de datos '{$dbName}' creada exitosamente");

            // Paso 3: Ejecutar migraciones
            $this->info('🔄 Ejecutando migraciones...');
            $this->phDatabaseService->ejecutarMigraciones($ph);
            $this->info('✅ Migraciones ejecutadas exitosamente');

            $this->newLine();
            $this->info('🎉 ¡Propiedad Horizontal creada exitosamente!');
            $this->newLine();
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $ph->id],
                    ['NIT', $ph->nit],
                    ['Nombre', $ph->nombre],
                    ['Base de Datos', $ph->db_name],
                    ['Estado', $ph->estado],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error al crear la PH:');
            $this->error("  {$e->getMessage()}");
            
            // Intentar limpiar si se creó el registro pero falló la base de datos
            if (isset($ph)) {
                $this->warn('⚠️  Limpiando registro creado...');
                try {
                    $ph->delete();
                } catch (\Exception $deleteException) {
                    $this->error("  No se pudo eliminar el registro: {$deleteException->getMessage()}");
                }
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Genera un nombre de base de datos basado en el NIT y nombre.
     * 
     * @param string $nit NIT del PH
     * @param string $nombre Nombre del PH
     * @return string Nombre de la base de datos generado
     */
    protected function generarNombreBaseDatos(string $nit, string $nombre): string
    {
        // Convertir nombre a formato válido para base de datos
        $nombreLimpio = strtolower($nombre);
        $nombreLimpio = preg_replace('/[^a-z0-9]+/', '_', $nombreLimpio);
        $nombreLimpio = trim($nombreLimpio, '_');
        
        return "ph_{$nombreLimpio}_{$nit}";
    }
}
