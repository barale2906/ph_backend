<?php

namespace Tests\Feature;

use App\Models\Ph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas de gestión de usuarios.
 * 
 * Valida:
 * - Crear usuarios (solo SUPER_ADMIN)
 * - Listar usuarios
 * - Ver usuario
 * - Actualizar usuario
 * - Eliminar usuario
 * - Asignar/remover acceso a PHs
 * - Permisos según roles
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminPh;
    protected User $regularUser;
    protected Ph $ph;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuarios de prueba
        $this->superAdmin = User::factory()->create([
            'rol' => 'SUPER_ADMIN',
        ]);

        $this->adminPh = User::factory()->create([
            'rol' => 'ADMIN_PH',
        ]);

        $this->regularUser = User::factory()->create([
            'rol' => 'LOGISTICA',
        ]);

        // Crear PH
        $this->ph = Ph::create([
            'nit' => '900123456',
            'nombre' => 'PH Test',
            'db_name' => 'ph_test',
            'estado' => 'activo',
        ]);

        // Asignar PH al admin
        $this->adminPh->phs()->attach($this->ph->id, ['rol' => 'ADMIN_PH']);
    }

    /**
     * Test: SUPER_ADMIN puede crear usuarios.
     */
    public function test_super_admin_can_create_user(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/users', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rol' => 'ADMIN_PH',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'rol',
                ],
            ])
            ->assertJson([
                'data' => [
                    'email' => 'nuevo@example.com',
                    'rol' => 'ADMIN_PH',
                ],
            ]);

        // Verificar que el usuario fue creado
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
        ]);
    }

    /**
     * Test: ADMIN_PH no puede crear usuarios.
     */
    public function test_admin_ph_cannot_create_user(): void
    {
        $token = $this->adminPh->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/users', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rol' => 'ADMIN_PH',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: SUPER_ADMIN puede listar todos los usuarios.
     */
    public function test_super_admin_can_list_all_users(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'rol', 'phs'],
                ],
            ]);

        // Debe incluir todos los usuarios
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test: ADMIN_PH puede ver usuarios de sus PHs.
     */
    public function test_admin_ph_can_view_users_from_his_phs(): void
    {
        // Crear otro usuario y asignarlo a la PH
        $otherUser = User::factory()->create(['rol' => 'LOGISTICA']);
        $otherUser->phs()->attach($this->ph->id, ['rol' => 'LOGISTICA']);

        $token = $this->adminPh->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/users');

        $response->assertStatus(200);
        
        // Debe ver al menos su propio usuario y el otro usuario de su PH
        $userIds = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->adminPh->id, $userIds);
        $this->assertContains($otherUser->id, $userIds);
    }

    /**
     * Test: Usuario puede ver su propia información.
     */
    public function test_user_can_view_own_information(): void
    {
        $token = $this->regularUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/users/{$this->regularUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->regularUser->id,
                'email' => $this->regularUser->email,
            ]);
    }

    /**
     * Test: SUPER_ADMIN puede actualizar cualquier usuario.
     */
    public function test_super_admin_can_update_any_user(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/users/{$this->regularUser->id}", [
                'name' => 'Nombre Actualizado',
                'rol' => 'ADMIN_PH',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Nombre Actualizado',
                    'rol' => 'ADMIN_PH',
                ],
            ]);

        $this->regularUser->refresh();
        $this->assertEquals('Nombre Actualizado', $this->regularUser->name);
        $this->assertEquals('ADMIN_PH', $this->regularUser->rol);
    }

    /**
     * Test: Usuario puede actualizar su propia información (excepto rol).
     */
    public function test_user_can_update_own_information_except_role(): void
    {
        $token = $this->regularUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/users/{$this->regularUser->id}", [
                'name' => 'Mi Nombre Actualizado',
                'email' => 'nuevoemail@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Mi Nombre Actualizado',
                    'email' => 'nuevoemail@example.com',
                ],
            ]);

        // El rol no debe cambiar (aunque se intente)
        $this->regularUser->refresh();
        $this->assertEquals('LOGISTICA', $this->regularUser->rol);
    }

    /**
     * Test: SUPER_ADMIN puede eliminar usuarios.
     */
    public function test_super_admin_can_delete_user(): void
    {
        $userToDelete = User::factory()->create(['rol' => 'LOGISTICA']);
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuario eliminado exitosamente',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    /**
     * Test: No se puede auto-eliminar.
     */
    public function test_user_cannot_delete_self(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/users/{$this->superAdmin->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No puedes eliminar tu propia cuenta',
            ]);
    }

    /**
     * Test: Asignar acceso a PH exitosamente.
     */
    public function test_assign_ph_access_successfully(): void
    {
        $newPh = Ph::create([
            'nit' => '900999999',
            'nombre' => 'PH Nueva',
            'db_name' => 'ph_nueva',
            'estado' => 'activo',
        ]);

        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/users/{$this->regularUser->id}/assign-ph", [
                'ph_id' => $newPh->id,
                'rol' => 'LOGISTICA',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Acceso asignado exitosamente',
            ]);

        // Verificar que el acceso fue asignado
        $this->assertTrue($this->regularUser->fresh()->tieneAccesoPh($newPh->id));
    }

    /**
     * Test: Remover acceso a PH exitosamente.
     */
    public function test_remove_ph_access_successfully(): void
    {
        // Asignar acceso primero
        $this->regularUser->phs()->attach($this->ph->id, ['rol' => 'LOGISTICA']);

        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/users/{$this->regularUser->id}/remove-ph", [
                'ph_id' => $this->ph->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Acceso removido exitosamente',
            ]);

        // Verificar que el acceso fue removido
        $this->assertFalse($this->regularUser->fresh()->tieneAccesoPh($this->ph->id));
    }

    /**
     * Test: Crear usuario requiere validación.
     */
    public function test_create_user_requires_validation(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/users', [
                'name' => 'Test',
                // Falta email, password, etc.
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'rol']);
    }

    /**
     * Test: Email debe ser único al crear usuario.
     */
    public function test_email_must_be_unique_when_creating_user(): void
    {
        $token = $this->superAdmin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/users', [
                'name' => 'Nuevo Usuario',
                'email' => $this->regularUser->email, // Email duplicado
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rol' => 'ADMIN_PH',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
