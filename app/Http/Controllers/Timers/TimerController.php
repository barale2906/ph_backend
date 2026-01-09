<?php

namespace App\Http\Controllers\Timers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timers\StoreTimerRequest;
use App\Http\Requests\Timers\UpdateTimerRequest;
use App\Http\Resources\Timers\TimerResource;
use App\Models\Timers\Timer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Timers (Cronómetros)
 * 
 * Controlador para la gestión de cronómetros.
 * Permite crear, listar, ver, actualizar y eliminar timers.
 * También permite iniciar, pausar y finalizar timers.
 * 
 * IMPORTANTE: Requiere middleware 'tenant' para acceder a la base de datos de la PH.
 * Solo el backend puede cerrar timers automáticamente.
 */
class TimerController extends Controller
{
    /**
     * Listar timers
     * 
     * Obtiene una lista de cronómetros, opcionalmente filtrados por reunión o tipo.
     * 
     * @authenticated
     * 
     * @queryParam reunion_id integer ID de la reunión para filtrar. Example: 1
     * @queryParam tipo string Filtrar por tipo (INTERVENCION, VOTACION). Example: INTERVENCION
     * @queryParam estado string Filtrar por estado (inactivo, activo, pausado, finalizado). Example: activo
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "reunion_id": 1,
     *       "tipo": "INTERVENCION",
     *       "duracion_segundos": 300,
     *       "estado": "activo",
     *       "tiempo_restante": 180
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Timer::query()
            ->when(request('reunion_id'), fn($q, $id) => $q->where('reunion_id', $id))
            ->when(request('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when(request('estado'), fn($q, $estado) => $q->where('estado', $estado))
            ->orderBy('created_at', 'desc');

        return TimerResource::collection($query->get());
    }

    /**
     * Crear nuevo timer
     * 
     * Crea un nuevo cronómetro para una reunión.
     * Solo ADMIN_PH y LOGISTICA pueden crear timers.
     * 
     * @authenticated
     * 
     * @bodyParam reunion_id integer required ID de la reunión. Example: 1
     * @bodyParam tipo string required Tipo (INTERVENCION, VOTACION). Example: INTERVENCION
     * @bodyParam duracion_segundos integer required Duración en segundos (1-3600). Example: 300
     * @bodyParam estado string Estado inicial (inactivo, activo, pausado, finalizado). Default: inactivo. Example: inactivo
     * 
     * @response 201 {
     *   "message": "Timer creado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "reunion_id": 1,
     *     "tipo": "INTERVENCION",
     *     "duracion_segundos": 300,
     *     "estado": "inactivo"
     *   }
     * }
     * 
     * @param StoreTimerRequest $request
     * @return JsonResponse
     */
    public function store(StoreTimerRequest $request): JsonResponse
    {
        try {
            $timer = Timer::create($request->validated());

            return response()->json([
                'message' => 'Timer creado exitosamente',
                'data' => new TimerResource($timer)
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Ver timer específico
     * 
     * Obtiene los detalles de un cronómetro específico.
     * 
     * @authenticated
     * 
     * @urlParam timer integer required ID del timer. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "reunion_id": 1,
     *   "tipo": "INTERVENCION",
     *   "duracion_segundos": 300,
     *   "estado": "activo",
     *   "tiempo_restante": 180
     * }
     * 
     * @param Timer $timer
     * @return TimerResource
     */
    public function show(Timer $timer): TimerResource
    {
        $timer->load('reunion');
        
        return new TimerResource($timer);
    }

    /**
     * Actualizar timer
     * 
     * Actualiza los datos de un cronómetro existente.
     * Solo ADMIN_PH y LOGISTICA pueden actualizar timers.
     * 
     * @authenticated
     * 
     * @urlParam timer integer required ID del timer. Example: 1
     * @bodyParam tipo string Tipo (INTERVENCION, VOTACION). Example: VOTACION
     * @bodyParam duracion_segundos integer Duración en segundos (1-3600). Example: 600
     * @bodyParam estado string Estado (inactivo, activo, pausado, finalizado). Example: activo
     * 
     * @response 200 {
     *   "message": "Timer actualizado exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdateTimerRequest $request
     * @param Timer $timer
     * @return JsonResponse
     */
    public function update(UpdateTimerRequest $request, Timer $timer): JsonResponse
    {
        try {
            $timer->update($request->validated());

            return response()->json([
                'message' => 'Timer actualizado exitosamente',
                'data' => new TimerResource($timer->fresh())
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Eliminar timer
     * 
     * Elimina un cronómetro del sistema.
     * Solo ADMIN_PH puede eliminar timers.
     * 
     * @authenticated
     * 
     * @urlParam timer integer required ID del timer. Example: 1
     * 
     * @response 200 {
     *   "message": "Timer eliminado exitosamente"
     * }
     * 
     * @param Timer $timer
     * @return JsonResponse
     */
    public function destroy(Timer $timer): JsonResponse
    {
        // No se puede eliminar si está activo
        if ($timer->estaActivo()) {
            return response()->json([
                'message' => 'No se puede eliminar un timer que está activo'
            ], 422);
        }

        $timer->delete();

        return response()->json([
            'message' => 'Timer eliminado exitosamente'
        ]);
    }

    /**
     * Iniciar timer
     * 
     * Inicia un cronómetro, cambiando su estado a 'activo'.
     * Solo puede haber un timer activo por tipo y reunión.
     * 
     * @authenticated
     * 
     * @urlParam timer integer required ID del timer. Example: 1
     * 
     * @response 200 {
     *   "message": "Timer iniciado exitosamente",
     *   "data": {...}
     * }
     * 
     * @response 422 {
     *   "message": "Ya existe un cronómetro ACTIVO de tipo 'INTERVENCION' para la reunión #1"
     * }
     * 
     * @param Timer $timer
     * @return JsonResponse
     */
    public function iniciar(Timer $timer): JsonResponse
    {
        try {
            $timer->iniciar();

            return response()->json([
                'message' => 'Timer iniciado exitosamente',
                'data' => new TimerResource($timer->fresh())
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Pausar timer
     * 
     * Pausa un cronómetro activo, cambiando su estado a 'pausado'.
     * 
     * @authenticated
     * 
     * @urlParam timer integer required ID del timer. Example: 1
     * 
     * @response 200 {
     *   "message": "Timer pausado exitosamente",
     *   "data": {...}
     * }
     * 
     * @param Timer $timer
     * @return JsonResponse
     */
    public function pausar(Timer $timer): JsonResponse
    {
        if (!$timer->estaActivo()) {
            return response()->json([
                'message' => 'Solo se puede pausar un timer que está activo'
            ], 422);
        }

        $timer->pausar();

        return response()->json([
            'message' => 'Timer pausado exitosamente',
            'data' => new TimerResource($timer->fresh())
        ]);
    }
}
