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
use Tests\TestCase;

/**
 * Pruebas de votaciones.
 * 
 * Valida:
 * - Un voto por pregunta por inmueble
 * - Replicación automática por coeficiente
 * - Bloqueo al cerrar votación
 */
class VotingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Ph $ph;
    protected Reunion $reunion;
    protected Pregunta $pregunta;
    protected Opcion $opcionSi;
    protected Opcion $opcionNo;
    protected Inmueble $inmueble;

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

        // Configurar conexión solo para crear datos en setUp
        // En las pruebas reales, el middleware configura la conexión automáticamente
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

        $this->inmueble = Inmueble::create([
            'numero' => '101',
            'coeficiente' => 1.5,
        ]);
    }

    /**
     * Configurar conexión del PH para crear datos directamente.
     * Solo se usa en setUp() para crear datos de prueba.
     * En las pruebas reales, el middleware configura la conexión.
     */
    protected function configurarConexionPh(): void
    {
        // Limpiar cache
        app(TenantResolver::class)->clearCache($this->ph->nit);
        
        // Configurar conexión directamente solo para crear datos en setUp
        $baseConfig = Config::get('database.connections.pgsql');
        $config = array_merge($baseConfig, [
            'database' => $this->ph->db_name,
        ]);
        
        Config::set("database.connections.ph_database", $config);
        DB::purge('ph_database');
        DB::setDefaultConnection('ph_database');
    }

    /**
     * Test: Un inmueble solo puede votar una vez por pregunta.
     */
    public function test_one_vote_per_immueble_per_question(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Registrar primer voto
        $response1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-PH-NIT', $this->ph->nit)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcionSi->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        $response1->assertStatus(202); // Accepted - procesamiento asíncrono

        // Esperar a que se procese el job (en pruebas, los jobs se ejecutan sincrónicamente)
        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        // Intentar votar de nuevo con el mismo inmueble
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-PH-NIT', $this->ph->nit)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcionNo->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        // Debe fallar porque ya votó
        $response2->assertStatus(422);
        $response2->assertJson([
            'message' => "El inmueble #{$this->inmueble->id} ya votó en la pregunta #{$this->pregunta->id}. Un inmueble solo puede votar una vez por pregunta."
        ]);
    }

    /**
     * Test: El coeficiente se guarda correctamente.
     */
    public function test_coefficient_is_stored_correctly(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Registrar voto
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-PH-NIT', $this->ph->nit)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcionSi->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        $response->assertStatus(202);

        // Procesar job
        $this->artisan('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        // Verificar que el coeficiente se guardó correctamente
        $voto = Voto::where('pregunta_id', $this->pregunta->id)
            ->where('inmueble_id', $this->inmueble->id)
            ->first();

        $this->assertNotNull($voto);
        $this->assertEquals(1.5, $voto->coeficiente);
    }

    /**
     * Test: No se puede votar en una pregunta cerrada.
     */
    public function test_cannot_vote_on_closed_question(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Cerrar la pregunta
        $this->pregunta->cerrar();

        // Intentar votar
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-PH-NIT', $this->ph->nit)
            ->postJson('/api/votos', [
                'pregunta_id' => $this->pregunta->id,
                'opcion_id' => $this->opcionSi->id,
                'inmueble_id' => $this->inmueble->id,
            ]);

        // Debe fallar
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "No se puede votar en una pregunta que no está abierta."
        ]);
    }
}
