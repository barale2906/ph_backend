<?php

namespace App\Http\Controllers\Votaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Votaciones\StoreOpcionRequest;
use App\Http\Requests\Votaciones\UpdateOpcionRequest;
use App\Http\Resources\Votaciones\OpcionResource;
use App\Models\Votaciones\Opcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Votaciones - Opciones
 * 
 * Controlador para la gestión de opciones de respuesta para preguntas.
 * Permite crear, listar, ver, actualizar y eliminar opciones.
 */
class OpcionController extends Controller
{
    /**
     * Listar opciones
     * 
     * Obtiene una lista de opciones, opcionalmente filtradas por pregunta.
     * 
     * @authenticated
     * 
     * @queryParam pregunta_id integer ID de la pregunta para filtrar. Example: 1
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "pregunta_id": 1,
     *       "texto": "Sí",
     *       "orden": 1
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Opcion::query()
            ->when(request('pregunta_id'), fn($q, $id) => $q->where('pregunta_id', $id))
            ->orderBy('orden')
            ->orderBy('id');

        return OpcionResource::collection($query->get());
    }

    /**
     * Crear nueva opción
     * 
     * Crea una nueva opción de respuesta para una pregunta.
     * Solo ADMIN_PH y LOGISTICA pueden crear opciones.
     * 
     * @authenticated
     * 
     * @bodyParam pregunta_id integer required ID de la pregunta. Example: 1
     * @bodyParam texto string required Texto de la opción. Example: Sí
     * @bodyParam orden integer Orden de visualización. Example: 1
     * 
     * @response 201 {
     *   "message": "Opción creada exitosamente",
     *   "data": {
     *     "id": 1,
     *     "pregunta_id": 1,
     *     "texto": "Sí",
     *     "orden": 1
     *   }
     * }
     * 
     * @param StoreOpcionRequest $request
     * @return JsonResponse
     */
    public function store(StoreOpcionRequest $request): JsonResponse
    {
        $opcion = Opcion::create($request->validated());

        return response()->json([
            'message' => 'Opción creada exitosamente',
            'data' => new OpcionResource($opcion)
        ], 201);
    }

    /**
     * Ver opción específica
     * 
     * Obtiene los detalles de una opción específica.
     * 
     * @authenticated
     * 
     * @urlParam opcion integer required ID de la opción. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "pregunta_id": 1,
     *   "texto": "Sí",
     *   "orden": 1
     * }
     * 
     * @param Opcion $opcion
     * @return OpcionResource
     */
    public function show(Opcion $opcion): OpcionResource
    {
        return new OpcionResource($opcion);
    }

    /**
     * Actualizar opción
     * 
     * Actualiza los datos de una opción existente.
     * Solo ADMIN_PH y LOGISTICA pueden actualizar opciones.
     * 
     * @authenticated
     * 
     * @urlParam opcion integer required ID de la opción. Example: 1
     * @bodyParam texto string Texto de la opción. Example: Sí (actualizado)
     * @bodyParam orden integer Orden de visualización. Example: 2
     * 
     * @response 200 {
     *   "message": "Opción actualizada exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdateOpcionRequest $request
     * @param Opcion $opcion
     * @return JsonResponse
     */
    public function update(UpdateOpcionRequest $request, Opcion $opcion): JsonResponse
    {
        // No se puede actualizar si la pregunta ya tiene votos
        if ($opcion->pregunta->votos()->exists()) {
            return response()->json([
                'message' => 'No se puede actualizar una opción de una pregunta que ya tiene votos registrados'
            ], 422);
        }

        $opcion->update($request->validated());

        return response()->json([
            'message' => 'Opción actualizada exitosamente',
            'data' => new OpcionResource($opcion->fresh())
        ]);
    }

    /**
     * Eliminar opción
     * 
     * Elimina una opción del sistema.
     * Solo ADMIN_PH puede eliminar opciones.
     * 
     * @authenticated
     * 
     * @urlParam opcion integer required ID de la opción. Example: 1
     * 
     * @response 200 {
     *   "message": "Opción eliminada exitosamente"
     * }
     * 
     * @param Opcion $opcion
     * @return JsonResponse
     */
    public function destroy(Opcion $opcion): JsonResponse
    {
        // No se puede eliminar si ya tiene votos
        if ($opcion->votos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una opción que ya tiene votos registrados'
            ], 422);
        }

        $opcion->delete();

        return response()->json([
            'message' => 'Opción eliminada exitosamente'
        ]);
    }
}
