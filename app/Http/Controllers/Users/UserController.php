<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\AssignPhRequest;
use App\Http\Resources\Users\UserResource;
use App\Models\User;
use App\Models\Ph;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @group Usuarios
 * 
 * Controlador para la gestión de usuarios del sistema.
 * Permite crear, listar, actualizar y eliminar usuarios, así como asignar acceso a PHs.
 */
class UserController extends Controller
{
    /**
     * Listar usuarios
     * 
     * Obtiene una lista de todos los usuarios del sistema.
     * Los usuarios no superadmin solo pueden ver usuarios de sus PHs.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Admin",
     *       "email": "admin@example.com",
     *       "rol": "SUPER_ADMIN",
     *       "phs": []
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when(!auth()->user()?->esSuperAdmin(), function ($query) {
                // Usuarios no superadmin solo ven usuarios de sus PHs
                $query->whereHas('phs', function ($q) {
                    $q->whereIn('phs.id', function ($subQuery) {
                        $subQuery->select('ph_id')
                            ->from('usuario_ph')
                            ->where('usuario_id', auth()->id());
                    });
                })->orWhere('id', auth()->id());
            })
            ->with('phs')
            ->get();

        return UserResource::collection($users);
    }

    /**
     * Crear usuario
     * 
     * Crea un nuevo usuario en el sistema.
     * Solo los superadministradores pueden crear usuarios.
     * 
     * @authenticated
     * 
     * @bodyParam name string required Nombre del usuario. Example: Juan Pérez
     * @bodyParam email string required Email único del usuario. Example: juan@example.com
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Example: password123
     * @bodyParam password_confirmation string required Confirmación de contraseña. Example: password123
     * @bodyParam rol string required Rol del usuario (SUPER_ADMIN, ADMIN_PH, LOGISTICA, LECTURA). Example: ADMIN_PH
     * 
     * @response 201 {
     *   "message": "Usuario creado exitosamente",
     *   "data": {
     *     "id": 2,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "rol": "ADMIN_PH",
     *     "phs": []
     *   }
     * }
     * 
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'data' => new UserResource($user->load('phs'))
        ], 201);
    }

    /**
     * Ver usuario específico
     * 
     * Obtiene los detalles de un usuario específico.
     * 
     * @authenticated
     * 
     * @urlParam user integer required ID del usuario. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "name": "Admin",
     *   "email": "admin@example.com",
     *   "rol": "SUPER_ADMIN",
     *   "phs": []
     * }
     * 
     * @param User $user
     * @return UserResource
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('phs'));
    }

    /**
     * Actualizar usuario
     * 
     * Actualiza los datos de un usuario existente.
     * 
     * @authenticated
     * 
     * @urlParam user integer required ID del usuario. Example: 1
     * @bodyParam name string Nombre del usuario. Example: Juan Pérez Actualizado
     * @bodyParam email string Email único del usuario. Example: juan.actualizado@example.com
     * @bodyParam rol string Rol del usuario (SUPER_ADMIN, ADMIN_PH, LOGISTICA, LECTURA). Example: ADMIN_PH
     * 
     * @response 200 {
     *   "message": "Usuario actualizado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "name": "Juan Pérez Actualizado",
     *     "email": "juan.actualizado@example.com",
     *     "rol": "ADMIN_PH",
     *     "phs": []
     *   }
     * }
     * 
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        
        // Si se actualiza la contraseña, hashearla
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'data' => new UserResource($user->fresh()->load('phs'))
        ]);
    }

    /**
     * Eliminar usuario
     * 
     * Elimina un usuario del sistema.
     * Solo los superadministradores pueden eliminar usuarios.
     * 
     * @authenticated
     * 
     * @urlParam user integer required ID del usuario. Example: 1
     * 
     * @response 200 {
     *   "message": "Usuario eliminado exitosamente"
     * }
     * 
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // No permitir auto-eliminación
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    /**
     * Asignar acceso a PH
     * 
     * Asigna o actualiza el acceso de un usuario a una Propiedad Horizontal específica.
     * 
     * @authenticated
     * 
     * @urlParam user integer required ID del usuario. Example: 1
     * @bodyParam ph_id integer required ID de la PH. Example: 1
     * @bodyParam rol string required Rol del usuario en esta PH (ADMIN_PH, LOGISTICA, LECTURA). Example: ADMIN_PH
     * 
     * @response 200 {
     *   "message": "Acceso asignado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "rol": "ADMIN_PH",
     *     "phs": [
     *       {
     *         "id": 1,
     *         "nit": "900123456",
     *         "nombre": "PH Ejemplo",
     *         "rol": "ADMIN_PH"
     *       }
     *     ]
     *   }
     * }
     * 
     * @param AssignPhRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function assignPh(AssignPhRequest $request, User $user): JsonResponse
    {
        $ph = Ph::findOrFail($request->ph_id);

        // Verificar que el usuario que asigna tiene acceso a esa PH
        if (!auth()->user()?->esSuperAdmin() && !auth()->user()?->tieneAccesoPh($ph->id)) {
            return response()->json([
                'message' => 'No tienes permiso para asignar acceso a esta PH'
            ], 403);
        }

        // Sincronizar el acceso (crea o actualiza)
        $user->phs()->syncWithoutDetaching([
            $ph->id => ['rol' => $request->rol]
        ]);

        return response()->json([
            'message' => 'Acceso asignado exitosamente',
            'data' => new UserResource($user->fresh()->load('phs'))
        ]);
    }

    /**
     * Remover acceso a PH
     * 
     * Remueve el acceso de un usuario a una Propiedad Horizontal específica.
     * 
     * @authenticated
     * 
     * @urlParam user integer required ID del usuario. Example: 1
     * @bodyParam ph_id integer required ID de la PH. Example: 1
     * 
     * @response 200 {
     *   "message": "Acceso removido exitosamente",
     *   "data": {
     *     "id": 1,
     *     "name": "Juan Pérez",
     *     "email": "juan@example.com",
     *     "rol": "ADMIN_PH",
     *     "phs": []
     *   }
     * }
     * 
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function removePh(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'ph_id' => ['required', 'integer', 'exists:phs,id']
        ]);

        $ph = Ph::findOrFail($request->ph_id);

        // Verificar que el usuario que remueve tiene acceso a esa PH
        if (!auth()->user()?->esSuperAdmin() && !auth()->user()?->tieneAccesoPh($ph->id)) {
            return response()->json([
                'message' => 'No tienes permiso para remover acceso a esta PH'
            ], 403);
        }

        $user->phs()->detach($ph->id);

        return response()->json([
            'message' => 'Acceso removido exitosamente',
            'data' => new UserResource($user->fresh()->load('phs'))
        ]);
    }
}
