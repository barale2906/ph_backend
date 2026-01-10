<?php

namespace App\Http\Controllers\Phs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phs\StorePhRequest;
use App\Http\Requests\Phs\UpdatePhRequest;
use App\Http\Resources\Phs\PhResource;
use App\Models\Ph;
use App\Services\PhDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group PHs
 * 
 * Controlador para la gestión de Propiedades Horizontales (PHs).
 * Permite crear, listar, ver, actualizar y eliminar PHs del sistema.
 */
class PhController extends Controller
{
    /**
     * Listar PHs
     * 
     * Obtiene una lista de todas las Propiedades Horizontales.
     * Los usuarios no superadmin solo verán los PHs a los que tienen acceso.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "nit": "900123456",
     *       "nombre": "PH Ejemplo",
     *       "db_name": "ph_ejemplo_900123456",
     *       "estado": "activo",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ph::class);

        $phs = Ph::query()
            ->when(!auth()->user()?->esSuperAdmin(), function ($query) {
                $query->whereHas('usuarios', function ($q) {
                    $q->where('users.id', auth()->id());
                });
            })
            ->get();

        return PhResource::collection($phs);
    }

    /**
     * Crear nuevo PH
     * 
     * Crea una nueva Propiedad Horizontal en el sistema.
     * Solo los superadministradores pueden crear PHs.
     * 
     * Este endpoint crea:
     * - El registro del PH en la base de datos MASTER
     * - La base de datos física del PH
     * - Ejecuta todas las migraciones en la nueva base de datos
     * 
     * @authenticated
     * 
     * @bodyParam nit string required NIT único de la PH. Example: 900123456
     * @bodyParam nombre string required Nombre de la PH. Example: PH Ejemplo
     * @bodyParam db_name string required Nombre de la base de datos (solo letras minúsculas, números y guiones bajos). Example: ph_ejemplo_900123456
     * @bodyParam estado string Estado de la PH (activo/inactivo). Default: activo. Example: activo
     * @bodyParam crear_base_datos boolean Si se debe crear la base de datos y ejecutar migraciones. Default: true. Example: true
     * 
     * @response 201 {
     *   "message": "PH creado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "nit": "900123456",
     *     "nombre": "PH Ejemplo",
     *     "db_name": "ph_ejemplo_900123456",
     *     "estado": "activo",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The nit has already been taken.",
     *   "errors": {
     *     "nit": ["The nit has already been taken."]
     *   }
     * }
     * 
     * @param StorePhRequest $request
     * @return JsonResponse
     */
    public function store(StorePhRequest $request): JsonResponse
    {
        $phDatabaseService = app(PhDatabaseService::class);
        $crearBaseDatos = $request->input('crear_base_datos', true);

        try {
            // Crear registro del PH
            $ph = Ph::create($request->validated());

            // Crear base de datos y ejecutar migraciones si se solicita
            if ($crearBaseDatos) {
                $phDatabaseService->crearBaseDatos($ph);
            }

            return response()->json([
                'message' => 'PH creado exitosamente' . ($crearBaseDatos ? ' (base de datos y migraciones ejecutadas)' : ''),
                'data' => new PhResource($ph)
            ], 201);
        } catch (\Exception $e) {
            // Si falló la creación de la base de datos, eliminar el registro
            if (isset($ph)) {
                $ph->delete();
            }

            return response()->json([
                'message' => 'Error al crear la PH',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver PH específico
     * 
     * Obtiene los detalles de una Propiedad Horizontal específica.
     * El usuario debe tener acceso al PH solicitado.
     * 
     * @authenticated
     * 
     * @urlParam ph integer required ID del PH. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "nit": "900123456",
     *   "nombre": "PH Ejemplo",
     *   "db_name": "ph_ejemplo_900123456",
     *   "estado": "activo",
     *   "created_at": "2024-01-01T00:00:00.000000Z",
     *   "updated_at": "2024-01-01T00:00:00.000000Z"
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * 
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Ph] 999"
     * }
     * 
     * @param Ph $ph
     * @return PhResource
     */
    public function show(Ph $ph): PhResource
    {
        $this->authorize('view', $ph);

        return new PhResource($ph);
    }

    /**
     * Actualizar PH
     * 
     * Actualiza los datos de una Propiedad Horizontal existente.
     * Solo superadministradores o administradores del PH pueden actualizarlo.
     * 
     * @authenticated
     * 
     * @urlParam ph integer required ID del PH. Example: 1
     * @bodyParam nit string NIT único de la PH. Example: 900123456
     * @bodyParam nombre string Nombre de la PH. Example: PH Ejemplo Actualizado
     * @bodyParam db_name string Nombre de la base de datos. Example: ph_ejemplo_actualizado
     * @bodyParam estado string Estado de la PH (activo/inactivo). Example: activo
     * 
     * @response 200 {
     *   "message": "PH actualizado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "nit": "900123456",
     *     "nombre": "PH Ejemplo Actualizado",
     *     "db_name": "ph_ejemplo_900123456",
     *     "estado": "activo",
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * 
     * @param UpdatePhRequest $request
     * @param Ph $ph
     * @return JsonResponse
     */
    public function update(UpdatePhRequest $request, Ph $ph): JsonResponse
    {
        $ph->update($request->validated());

        return response()->json([
            'message' => 'PH actualizado exitosamente',
            'data' => new PhResource($ph->fresh())
        ]);
    }

    /**
     * Eliminar PH
     * 
     * Elimina una Propiedad Horizontal del sistema.
     * Solo los superadministradores pueden eliminar PHs.
     * 
     * @authenticated
     * 
     * @urlParam ph integer required ID del PH. Example: 1
     * 
     * @response 200 {
     *   "message": "PH eliminado exitosamente"
     * }
     * 
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * 
     * @param Ph $ph
     * @return JsonResponse
     */
    public function destroy(Ph $ph): JsonResponse
    {
        $this->authorize('delete', $ph);

        $ph->delete();

        return response()->json([
            'message' => 'PH eliminado exitosamente'
        ]);
    }
}

