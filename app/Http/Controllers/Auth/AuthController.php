<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\Users\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Autenticación
 * 
 * Controlador para la autenticación de usuarios.
 * Permite iniciar sesión, cerrar sesión y obtener información del usuario actual.
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión
     * 
     * Autentica un usuario y genera un token de acceso Sanctum.
     * 
     * @bodyParam email string required Email del usuario. Example: admin@example.com
     * @bodyParam password string required Contraseña del usuario. Example: password123
     * 
     * @response 200 {
     *   "token": "1|abcdef123456...",
     *   "user": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@example.com",
     *     "rol": "SUPER_ADMIN",
     *     "phs": []
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "Las credenciales proporcionadas son incorrectas.",
     *   "errors": {
     *     "email": ["Las credenciales proporcionadas son incorrectas."]
     *   }
     * }
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Revocar tokens anteriores (opcional - para forzar un solo dispositivo)
        // $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Cerrar sesión
     * 
     * Revoca el token de acceso actual del usuario.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "message": "Sesión cerrada exitosamente"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Usuario actual
     * 
     * Obtiene la información del usuario autenticado actualmente.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "id": 1,
     *   "name": "Admin",
     *   "email": "admin@example.com",
     *   "rol": "SUPER_ADMIN",
     *   "phs": [
     *     {
     *       "id": 1,
     *       "nit": "900123456",
     *       "nombre": "PH Ejemplo",
     *       "rol": "ADMIN_PH"
     *     }
     *   ]
     * }
     * 
     * @param Request $request
     * @return UserResource
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Cambiar contraseña
     * 
     * Permite al usuario cambiar su propia contraseña.
     * 
     * @authenticated
     * 
     * @bodyParam current_password string required Contraseña actual. Example: oldpassword123
     * @bodyParam password string required Nueva contraseña (mínimo 8 caracteres). Example: newpassword123
     * @bodyParam password_confirmation string required Confirmación de la nueva contraseña. Example: newpassword123
     * 
     * @response 200 {
     *   "message": "Contraseña actualizada exitosamente"
     * }
     * 
     * @response 422 {
     *   "message": "La contraseña actual es incorrecta.",
     *   "errors": {
     *     "current_password": ["La contraseña actual es incorrecta."]
     *   }
     * }
     * 
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }
}
