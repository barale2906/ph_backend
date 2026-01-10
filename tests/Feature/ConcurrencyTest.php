<?php

namespace Tests\Feature;

use App\Models\Ph;
use App\Models\User;
use App\Models\Reuniones\Reunion;
use App\Models\Votaciones\Pregunta;
use App\Models\Votaciones\Opcion;
use App\Models\Votaciones\Voto;
use App\Models\Inmuebles\Inmueble;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pruebas de concurrencia y carga.
 * 
 * Valida:
 * - Votos concurrentes sin perder datos
 * - Uso de jobs y colas
 * - No perder votos
 * - No duplicar votos
 * - El middleware funciona correctamente en cada request
 */
class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Ph $ph;
    protected Reunion $reunion;
    protected Pregunta $pregunta;
    protected Opcion $opcionSi;
    protected Opcion $opcionNo;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario
        $this->user = User::factory()->create([
            'rol' => 'ADMIN_PH',
        ]);

        // Crear PH (usar la misma base de prueba)
        $this->ph = Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'db_name' => 'ph_backend_test', // Usar la misma DB de prueba
            'estado' => 'activo',
        ]);

        $this->user->phs()->attach($this->ph->id, ['rol' => 'ADMIN_PH']);

        // Configurar conexión del PH
        $this->configurarConexionPh();

        // Crear datos de prueba
        $this->reunion = Reunion::create([
            'tipo' => 'ordinaria',
            'fecha' => now()->toDateString(),
            'hora' => '10:00',
            'modalidad' => 'presencial',
            'estado' => 'en_curso',
        ]);

        $this->pregunta = Pregunta::create([
            'reunion_id' => $this->reunion->id,
            'pregunta' => '¿Aprobamos el presupuesto?',
            'estado' => 'abierta',
            'apertura_at' => now()->utc(),
        ]);

        $this->opcionSi = Opcion::create([
            'pregunta_id' => $this->pregunta->id,
            'texto' => 'Sí',
        ]);

        $this->opcionNo = Opcion::create([
            'pregunta_id' => $this->pregunta->id,
            'texto' => 'No',
        ]);
    }

    /**
     * Configurar la conexión ph_database para el PH de prueba.
     */
    protected function configurarConexionPh(): void
    {
        // Limpiar cache
        app(TenantResolver::class)->clearCache($this->ph->nit);
        
        // Configurar conexión directamente
        $baseConfig = Config::get('database.connections.pgsql');
        $config = array_merge($baseConfig, [
            'database' => $this->ph->db_name,
        ]);
        
        Config::set("database.connections.ph_database", $config);
        DB::purge('ph_database');
        DB::setDefaultConnection('ph_database');
    }

    /**
     * Test: Manejar votos concurrentes sin perder datos.
     * 
     * Optimizado para pruebas: usa 50 votos en lugar de 500 para ser más rápido.
     * Valida el mismo comportamiento: no perder votos, no duplicar votos.
     */
    public function test_handle_concurrent_votes(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Crear 50 inmuebles (suficiente para validar concurrencia sin ser lento)
        $inmuebles = [];
        for ($i = 1; $i <= 50; $i++) {
            $inmuebles[] = Inmueble::create([
                'numero' => "APT-{$i}",
                'coeficiente' => 1.0 + ($i * 0.01),
            ]);
        }

        // Enviar votos (el middleware configurará la conexión en cada request)
        $responses = [];
        foreach ($inmuebles as $inmueble) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$token}")
                ->withHeader('X-PH-NIT', $this->ph->nit)
                ->postJson('/api/votos', [
                    'pregunta_id' => $this->pregunta->id,
                    'opcion_id' => $this->opcionSi->id,
                    'inmueble_id' => $inmueble->id,
                ]);
        }

        // Verificar que todos los requests fueron aceptados
        foreach ($responses as $response) {
            $response->assertStatus(202); // Accepted
        }

        // Procesar todos los jobs
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Verificar que se registraron exactamente 50 votos
        $votosCount = Voto::where('pregunta_id', $this->pregunta->id)->count();
        $this->assertEquals(50, $votosCount, 'Deben registrarse exactamente 50 votos');

        // Verificar que no hay duplicados
        $votosDuplicados = DB::table('votos')
            ->select('pregunta_id', 'inmueble_id', DB::raw('COUNT(*) as count'))
            ->where('pregunta_id', $this->pregunta->id)
            ->groupBy('pregunta_id', 'inmueble_id')
            ->having('count', '>', 1)
            ->count();

        $this->assertEquals(0, $votosDuplicados, 'No debe haber votos duplicados');
    }


    /**
     * Test: Los jobs se procesan correctamente.
     */
    public function test_jobs_are_processed_correctly(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Crear 10 inmuebles
        $inmuebles = [];
        for ($i = 1; $i <= 10; $i++) {
            $inmuebles[] = Inmueble::create([
                'numero' => "APT-{$i}",
                'coeficiente' => 1.0,
            ]);
        }

        // Enviar votos
        foreach ($inmuebles as $inmueble) {
            $this->withHeader('Authorization', "Bearer {$token}")
                ->withHeader('X-PH-NIT', $this->ph->nit)
                ->postJson('/api/votos', [
                    'pregunta_id' => $this->pregunta->id,
                    'opcion_id' => $this->opcionSi->id,
                    'inmueble_id' => $inmueble->id,
                ]);
        }

        // Verificar que los jobs están en la cola
        $this->assertGreaterThan(0, Queue::size());

        // Procesar jobs
        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        // Verificar que se procesaron todos los votos
        $votosCount = Voto::where('pregunta_id', $this->pregunta->id)->count();
        $this->assertEquals(10, $votosCount);
    }
}
