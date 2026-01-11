<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas de autenticación.
 * 
 * Valida:
 * - Login exitoso
 * - Login con credenciales incorrectas
 * - Logout
 * - Obtener usuario actual
 * - Cambiar contraseña
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'rol' => 'ADMIN_PH',
        ]);
    }

    /**
     * Test: Login exitoso retorna token y datos del usuario.
     */
    public function test_login_returns_token_and_user_data(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'rol',
                    'phs',
                ],
            ])
            ->assertJson([
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);

        // Verificar que el token es válido
        $token = $response->json('token');
        $this->assertNotEmpty($token);
    }

    /**
     * Test: Login con email incorrecto retorna 422.
     */
    public function test_login_with_invalid_email_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Login con contraseña incorrecta retorna 422.
     */
    public function test_login_with_invalid_password_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Login requiere email y password.
     */
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test: Obtener usuario actual retorna datos correctos.
     */
    public function test_me_returns_current_user(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
                'name' => $this->user->name,
                'rol' => $this->user->rol,
            ]);
    }

    /**
     * Test: Me requiere autenticación.
     */
    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    /**
     * Test: Logout revoca el token.
     */
    public function test_logout_revokes_token(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sesión cerrada exitosamente',
            ]);

        // Verificar que el token ya no es válido
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $meResponse->assertStatus(401);
    }

    /**
     * Test: Cambiar contraseña exitosamente.
     */
    public function test_change_password_successfully(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/change-password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Contraseña actualizada exitosamente',
            ]);

        // Verificar que la contraseña fue cambiada
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /**
     * Test: Cambiar contraseña con contraseña actual incorrecta retorna 422.
     */
    public function test_change_password_with_wrong_current_password_returns_422(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/change-password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Test: Cambiar contraseña requiere confirmación.
     */
    public function test_change_password_requires_confirmation(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/change-password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test: Cambiar contraseña requiere autenticación.
     */
    public function test_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/change-password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }
}
