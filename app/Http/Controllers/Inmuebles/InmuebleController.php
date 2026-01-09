<?php

namespace App\Http\Controllers\Inmuebles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmuebles\StoreInmuebleRequest;
use App\Http\Requests\Inmuebles\UpdateInmuebleRequest;
use App\Http\Resources\Inmuebles\InmuebleResource;
use App\Models\Inmuebles\Inmueble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Inmuebles
 * 
 * Controlador para la gestión de inmuebles.
 * Permite crear, listar, ver, actualizar y eliminar inmuebles.
 * 
 * IMPORTANTE: Requiere middleware 'tenant' para acceder a la base de datos de la PH.
 */
class InmuebleController extends Controller
{
    /**
     * Listar inmuebles
     * 
     * Obtiene una lista de inmuebles, opcionalmente filtrados.
     * 
     * @authenticated
     * 
     * @queryParam activo boolean Filtrar por estado activo. Example: true
     * @queryParam tipo string Filtrar por tipo. Example: apartamento
     * @queryParam nomenclatura string Filtrar por nomenclatura. Example: APT-101
     * @queryParam with_asistentes boolean Incluir asistentes en la respuesta. Example: false
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "nomenclatura": "APT-101",
     *       "coeficiente": 1.5,
     *       "tipo": "apartamento",
     *       "activo": true
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Inmueble::query()
            ->when(request('activo') !== null, fn($q) => $q->where('activo', request('activo')))
            ->when(request('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when(request('nomenclatura'), fn($q, $nom) => $q->where('nomenclatura', 'like', "%{$nom}%"))
            ->when(request('with_asistentes'), fn($q) => $q->with('asistentes'))
            ->orderBy('nomenclatura');

        return InmuebleResource::collection($query->get());
    }

    /**
     * Crear nuevo inmueble
     * 
     * Crea un nuevo inmueble en la PH.
     * Solo ADMIN_PH y LOGISTICA pueden crear inmuebles.
     * 
     * @authenticated
     * 
     * @bodyParam nomenclatura string required Código único del inmueble. Example: APT-101
     * @bodyParam coeficiente number required Coeficiente de copropiedad (0-100). Example: 1.5
     * @bodyParam tipo string required Tipo de inmueble. Example: apartamento
     * @bodyParam propietario_documento string Número de documento del propietario. Example: 1234567890
     * @bodyParam propietario_nombre string Nombre del propietario. Example: Juan Pérez
     * @bodyParam telefono string Teléfono de contacto. Example: +573001234567
     * @bodyParam email string Email de contacto. Example: juan@example.com
     * @bodyParam activo boolean Estado activo. Default: true. Example: true
     * 
     * @response 201 {
     *   "message": "Inmueble creado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "nomenclatura": "APT-101",
     *     "coeficiente": 1.5,
     *     "tipo": "apartamento"
     *   }
     * }
     * 
     * @param StoreInmuebleRequest $request
     * @return JsonResponse
     */
    public function store(StoreInmuebleRequest $request): JsonResponse
    {
        $inmueble = Inmueble::create($request->validated());

        return response()->json([
            'message' => 'Inmueble creado exitosamente',
            'data' => new InmuebleResource($inmueble)
        ], 201);
    }

    /**
     * Ver inmueble específico
     * 
     * Obtiene los detalles de un inmueble específico.
     * 
     * @authenticated
     * 
     * @urlParam inmueble integer required ID del inmueble. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "nomenclatura": "APT-101",
     *   "coeficiente": 1.5,
     *   "tipo": "apartamento",
     *   "propietario_nombre": "Juan Pérez",
     *   "activo": true
     * }
     * 
     * @param Inmueble $inmueble
     * @return InmuebleResource
     */
    public function show(Inmueble $inmueble): InmuebleResource
    {
        $inmueble->load('asistentes');
        
        return new InmuebleResource($inmueble);
    }

    /**
     * Actualizar inmueble
     * 
     * Actualiza los datos de un inmueble existente.
     * Solo ADMIN_PH y LOGISTICA pueden actualizar inmuebles.
     * 
     * @authenticated
     * 
     * @urlParam inmueble integer required ID del inmueble. Example: 1
     * @bodyParam nomenclatura string Código único del inmueble. Example: APT-101
     * @bodyParam coeficiente number Coeficiente de copropiedad (0-100). Example: 1.5
     * @bodyParam tipo string Tipo de inmueble. Example: apartamento
     * @bodyParam propietario_documento string Número de documento del propietario. Example: 1234567890
     * @bodyParam propietario_nombre string Nombre del propietario. Example: Juan Pérez
     * @bodyParam telefono string Teléfono de contacto. Example: +573001234567
     * @bodyParam email string Email de contacto. Example: juan@example.com
     * @bodyParam activo boolean Estado activo. Example: true
     * 
     * @response 200 {
     *   "message": "Inmueble actualizado exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdateInmuebleRequest $request
     * @param Inmueble $inmueble
     * @return JsonResponse
     */
    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble): JsonResponse
    {
        $inmueble->update($request->validated());

        return response()->json([
            'message' => 'Inmueble actualizado exitosamente',
            'data' => new InmuebleResource($inmueble->fresh())
        ]);
    }

    /**
     * Eliminar inmueble
     * 
     * Elimina un inmueble del sistema.
     * Solo ADMIN_PH puede eliminar inmuebles.
     * 
     * @authenticated
     * 
     * @urlParam inmueble integer required ID del inmueble. Example: 1
     * 
     * @response 200 {
     *   "message": "Inmueble eliminado exitosamente"
     * }
     * 
     * @param Inmueble $inmueble
     * @return JsonResponse
     */
    public function destroy(Inmueble $inmueble): JsonResponse
    {
        // No se puede eliminar si tiene asistentes o votos
        if ($inmueble->asistentes()->exists() || $inmueble->votos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un inmueble que tiene asistentes o votos asociados'
            ], 422);
        }

        $inmueble->delete();

        return response()->json([
            'message' => 'Inmueble eliminado exitosamente'
        ]);
    }
}
