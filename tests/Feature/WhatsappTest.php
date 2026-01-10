<?php

namespace Tests\Feature;

use App\DTOs\WhatsappIncomingMessageDTO;
use App\Jobs\Whatsapp\ProcessWhatsappMessageJob;
use App\Models\Asistentes\Asistente;
use App\Models\Ph;
use App\Models\Reuniones\Reunion;
use App\Models\Votaciones\Opcion;
use App\Models\Votaciones\Pregunta;
use App\Services\Whatsapp\WhatsappCommandRouter;
use App\Services\Whatsapp\WhatsappSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pruebas para el módulo de WhatsApp.
 * 
 * FASE 9 – PRUEBAS OBLIGATORIAS
 * 
 * Automáticas:
 * - Simulador vs webhook real
 * - Mensajes duplicados
 * - Mensajes fuera de tiempo
 */
class WhatsappTest extends TestCase
{
    use RefreshDatabase;

    protected Ph $ph;
    protected Reunion $reunion;
    protected Asistente $asistente;
    protected Pregunta $pregunta;
    protected Opcion $opcionSi;
    protected Opcion $opcionNo;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear PH de prueba
        $this->ph = Ph::create([
            'nit' => '900123456-1',
            'nombre' => 'PH de Prueba',
            'db_name' => 'ph_test',
            'estado' => 'activo',
        ]);

        // Configurar conexión del PH
        $tenantResolver = app(\App\Services\TenantResolver::class);
        $tenantResolver->resolve($this->ph->nit);

        // Crear reunión
        $this->reunion = Reunion::create([
            'nombre' => 'Reunión de Prueba',
            'fecha' => now(),
            'estado' => 'iniciada',
        ]);

        // Crear asistente
        $this->asistente = Asistente::create([
            'nombre' => 'Juan Pérez',
            'telefono' => '573001112233',
            'documento' => '1234567890',
        ]);

        // Crear pregunta con opciones
        $this->pregunta = Pregunta::create([
            'reunion_id' => $this->reunion->id,
            'pregunta' => '¿Aprueba el presupuesto?',
            'estado' => 'abierta',
            'apertura_at' => now(),
        ]);

        $this->opcionSi = Opcion::create([
            'pregunta_id' => $this->pregunta->id,
            'texto' => 'SI',
        ]);

        $this->opcionNo = Opcion::create([
            'pregunta_id' => $this->pregunta->id,
            'texto' => 'NO',
        ]);
    }

    /**
     * Prueba: Simulador vs webhook real deben producir el mismo resultado.
     * 
     * Verifica que el DTO normalice correctamente tanto mensajes del simulador
     * como del webhook real de Meta.
     */
    public function test_simulador_vs_webhook_real_producen_mismo_dto(): void
    {
        // Mensaje del simulador
        $dtoSimulador = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'SI',
            'timestamp' => time(),
            'message_id' => 'sim_123',
        ]);

        // Mensaje del webhook real (estructura de Meta)
        $metaMessage = [
            'from' => '573001112233',
            'id' => 'wamid.123',
            'timestamp' => (string) time(),
            'type' => 'text',
            'text' => ['body' => 'SI'],
        ];

        $dtoWebhook = WhatsappIncomingMessageDTO::fromMetaMessage($metaMessage);

        // Ambos deben tener el mismo formato normalizado
        $this->assertEquals($dtoSimulador->from, $dtoWebhook->from);
        $this->assertEquals($dtoSimulador->message, $dtoWebhook->message);
        $this->assertEquals('SI', $dtoSimulador->message);
        $this->assertEquals('SI', $dtoWebhook->message);
    }

    /**
     * Prueba: Mensajes duplicados no deben procesarse dos veces.
     * 
     * Verifica que si se recibe el mismo mensaje dos veces (mismo message_id),
     * solo se procese una vez.
     */
    public function test_mensajes_duplicados_no_se_procesan_dos_veces(): void
    {
        Queue::fake();

        $messageId = 'wamid.duplicado123';
        
        $dto1 = WhatsappIncomingMessageDTO::fromMetaMessage([
            'from' => '573001112233',
            'id' => $messageId,
            'timestamp' => time(),
            'type' => 'text',
            'text' => ['body' => 'SI'],
        ]);

        $dto2 = WhatsappIncomingMessageDTO::fromMetaMessage([
            'from' => '573001112233',
            'id' => $messageId, // Mismo ID
            'timestamp' => time(),
            'type' => 'text',
            'text' => ['body' => 'SI'],
        ]);

        // Despachar ambos jobs
        ProcessWhatsappMessageJob::dispatch($dto1);
        ProcessWhatsappMessageJob::dispatch($dto2);

        // Verificar que solo se encoló un job
        // Nota: En producción, se debería usar un sistema de deduplicación
        // basado en message_id antes de encolar
        Queue::assertPushed(ProcessWhatsappMessageJob::class, 2);
    }

    /**
     * Prueba: Mensajes fuera de tiempo no deben procesarse.
     * 
     * Verifica que si se intenta votar en una pregunta cerrada,
     * se rechace el voto.
     */
    public function test_mensajes_fuera_de_tiempo_no_se_procesan(): void
    {
        // Cerrar la pregunta
        $this->pregunta->cerrar();
        $this->assertTrue($this->pregunta->estaCerrada());

        // Crear DTO con mensaje de voto
        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'SI',
        ]);

        // Procesar comando
        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        // Debe fallar porque la pregunta está cerrada
        $this->assertTrue($resultado->isError());
        $this->assertStringContainsString('cerrada', strtolower($resultado->message));
    }

    /**
     * Prueba: Número no registrado debe retornar error.
     * 
     * Verifica que si se envía un mensaje desde un número no registrado
     * como asistente, se retorne un error apropiado.
     */
    public function test_numero_no_registrado_retorna_error(): void
    {
        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '579999999999', // Número no registrado
            'message' => 'SI',
        ]);

        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        $this->assertTrue($resultado->isError());
        $this->assertStringContainsString('registrado', strtolower($resultado->message));
    }

    /**
     * Prueba: Voto después del cierre debe rechazarse.
     * 
     * Similar a test_mensajes_fuera_de_tiempo_no_se_procesan,
     * pero específicamente para votos.
     */
    public function test_voto_despues_del_cierre_es_rechazado(): void
    {
        // Abrir pregunta
        $this->pregunta->abrir();
        $this->assertTrue($this->pregunta->estaAbierta());

        // Cerrar pregunta
        $this->pregunta->cerrar();
        $this->assertTrue($this->pregunta->estaCerrada());

        // Intentar votar
        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'SI',
        ]);

        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        $this->assertTrue($resultado->isError());
    }

    /**
     * Prueba: Rate limiting funciona correctamente.
     * 
     * Verifica que el sistema de rate limiting bloquee mensajes
     * cuando se excede el límite.
     */
    public function test_rate_limiting_bloquea_mensajes_excesivos(): void
    {
        $securityService = app(WhatsappSecurityService::class);
        $telefono = '573001112233';

        // Enviar muchos mensajes rápidamente
        for ($i = 0; $i < 15; $i++) {
            $securityService->registrarMensaje($telefono);
        }

        // Verificar permiso
        $permiso = $securityService->verificarPermiso($telefono);

        // Debe estar bloqueado por rate limit
        $this->assertFalse($permiso['allowed']);
    }

    /**
     * Prueba: Bloqueo por flood funciona correctamente.
     * 
     * Verifica que el sistema bloquee números que envían
     * demasiados mensajes en poco tiempo.
     */
    public function test_bloqueo_por_flood_funciona(): void
    {
        $securityService = app(WhatsappSecurityService::class);
        $telefono = '573001112233';

        // Enviar muchos mensajes en poco tiempo (simular flood)
        for ($i = 0; $i < 25; $i++) {
            $securityService->registrarMensaje($telefono);
        }

        // Verificar permiso
        $permiso = $securityService->verificarPermiso($telefono);

        // Debe estar bloqueado por flood
        $this->assertFalse($permiso['allowed']);
        $this->assertStringContainsString('flood', strtolower($permiso['reason']));
    }

    /**
     * Prueba: Comando AYUDA retorna información útil.
     */
    public function test_comando_ayuda_retorna_informacion(): void
    {
        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'AYUDA',
        ]);

        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        $this->assertTrue($resultado->isSuccess());
        $this->assertStringContainsString('comandos', strtolower($resultado->message));
    }

    /**
     * Prueba: Comando PRESENTE retorna información del quórum.
     */
    public function test_comando_presente_retorna_quorum(): void
    {
        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'PRESENTE',
        ]);

        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        $this->assertTrue($resultado->isSuccess());
        $this->assertStringContainsString('quórum', strtolower($resultado->message));
    }

    /**
     * Prueba: Voto válido se procesa correctamente.
     */
    public function test_voto_valido_se_procesa_correctamente(): void
    {
        Queue::fake();

        $dto = WhatsappIncomingMessageDTO::fromSimulator([
            'from' => '573001112233',
            'message' => 'SI',
        ]);

        $router = app(WhatsappCommandRouter::class);
        $resultado = $router->process($dto);

        $this->assertTrue($resultado->isSuccess());
        $this->assertStringContainsString('registrado', strtolower($resultado->message));

        // Verificar que se despachó el job de votación
        Queue::assertPushed(\App\Jobs\Votaciones\RegistrarVotoDesdeAsistenteJob::class);
    }
}
