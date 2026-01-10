<?php

namespace Tests\Feature;

use App\Models\Ph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de seguridad.
 * 
 * Valida:
 * - JWT obligatorio
 * - Roles respetados
 * - Rate limit activo
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;
    protected Ph $ph;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuarios de prueba
        $this->user = User::factory()->create([
            'rol' => 'LOGISTICA',
        ]);

        $this->adminUser = User::factory()->create([
            'rol' => 'SUPER_ADMIN',
        ]);

        // Crear PH
        $this->ph = Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'db_name' => 'ph_test',
            'estado' => 'activo',
        ]);

        $this->user->phs()->attach($this->ph->id, ['rol' => 'LOGISTICA']);
    }

    /**
     * Test: Endpoints sin token retornan 401.
     */
    public function test_endpoints_without_token_return_401(): void
    {
        // Intentar acceder sin token
        $response = $this->getJson('/api/reuniones');

        $response->assertStatus(401);
    }

    /**
     * Test: Endpoints con token inválido retornan 401.
     */
    public function test_endpoints_with_invalid_token_return_401(): void
    {
        // Intentar acceder con token inválido
        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/api/reuniones');

        $response->assertStatus(401);
    }

    /**
     * Test: Roles son respetados.
     */
    public function test_roles_are_respected(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Intentar crear un PH (solo SUPER_ADMIN puede)
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/phs', [
                'nit' => '900999999',
                'nombre' => 'PH Nueva',
                'db_name' => 'ph_nueva',
                'estado' => 'activo',
            ]);

        // Debe fallar por falta de permisos
        $response->assertStatus(403);
    }

    /**
     * Test: SUPER_ADMIN puede crear PHs.
     */
    public function test_super_admin_can_create_ph(): void
    {
        $token = $this->adminUser->createToken('test-token')->plainTextToken;

        // Crear PH como SUPER_ADMIN
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/phs', [
                'nit' => '900999999',
                'nombre' => 'PH Nueva',
                'db_name' => 'ph_nueva',
                'estado' => 'activo',
                'crear_base_datos' => false, // No crear DB en pruebas
            ]);

        // Debe tener éxito
        $response->assertStatus(201);
    }

    /**
     * Test: Rate limit está activo.
     * 
     * Nota: Este test puede necesitar ajustes según la configuración de rate limiting.
     */
    public function test_rate_limit_is_active(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Hacer múltiples requests rápidos
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->withHeader('Authorization', "Bearer {$token}")
                ->withHeader('X-PH-NIT', $this->ph->nit)
                ->getJson('/api/reuniones');
        }

        // Verificar que al menos algunas requests fueron limitadas
        // (esto depende de la configuración de rate limiting)
        $statusCodes = array_map(fn($r) => $r->status(), $responses);
        $this->assertContains(429, $statusCodes, 'Rate limiting debería estar activo');
    }
}
