<?php

namespace App\Http\Controllers\Votaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Votaciones\StoreVotoRequest;
use App\Http\Resources\Votaciones\VotoResource;
use App\Services\VotacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Votaciones - Votos
 * 
 * Controlador para la gestión de votos.
 * Permite registrar votos y consultar votos existentes.
 * 
 * IMPORTANTE: Los votos son inmutables una vez registrados.
 */
class VotoController extends Controller
{
    /**
     * Constructor.
     * 
     * @param VotacionService $votacionService
     */
    public function __construct(
        protected VotacionService $votacionService
    ) {
        //
    }

    /**
     * Listar votos
     * 
     * Obtiene una lista de votos, opcionalmente filtrados por pregunta o inmueble.
     * 
     * @authenticated
     * 
     * @queryParam pregunta_id integer ID de la pregunta para filtrar. Example: 1
     * @queryParam inmueble_id integer ID del inmueble para filtrar. Example: 1
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "pregunta_id": 1,
     *       "inmueble_id": 5,
     *       "opcion_id": 2,
     *       "coeficiente": 1.5,
     *       "telefono": "+573001234567",
     *       "votado_at": "2024-01-01T10:00:00Z"
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = \App\Models\Votaciones\Voto::query()
            ->with(['pregunta', 'inmueble', 'opcion'])
            ->when(request('pregunta_id'), fn($q, $id) => $q->where('pregunta_id', $id))
            ->when(request('inmueble_id'), fn($q, $id) => $q->where('inmueble_id', $id))
            ->orderBy('votado_at', 'desc');

        return VotoResource::collection($query->get());
    }

    /**
     * Registrar voto
     * 
     * Registra un voto para un inmueble o desde un asistente.
     * Si se proporciona asistente_id, se replicará el voto para todos los inmuebles del asistente.
     * 
     * IMPORTANTE: Los votos son inmutables una vez registrados.
     * 
     * @bodyParam pregunta_id integer required ID de la pregunta. Example: 1
     * @bodyParam opcion_id integer required ID de la opción seleccionada. Example: 2
     * @bodyParam inmueble_id integer ID del inmueble (requerido si no se proporciona asistente_id). Example: 5
     * @bodyParam asistente_id integer ID del asistente (requerido si no se proporciona inmueble_id). Example: 10
     * @bodyParam telefono string Teléfono desde el cual se votó (opcional). Example: +573001234567
     * 
     * @response 201 {
     *   "message": "Voto(s) registrado(s) exitosamente",
     *   "data": [
     *     {
     *       "id": 1,
     *       "pregunta_id": 1,
     *       "inmueble_id": 5,
     *       "opcion_id": 2,
     *       "coeficiente": 1.5
     *     }
     *   ]
     * }
     * 
     * @response 422 {
     *   "message": "No se puede votar en una pregunta que no está abierta"
     * }
     * 
     * @param StoreVotoRequest $request
     * @return JsonResponse
     */
    public function store(StoreVotoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // En FASE 7, todas las escrituras van por cola
            // Si se proporciona asistente_id, usar el método de replicación
            if (isset($validated['asistente_id'])) {
                $this->votacionService->registrarVotoDesdeAsistente(
                    preguntaId: $validated['pregunta_id'],
                    opcionId: $validated['opcion_id'],
                    asistenteId: $validated['asistente_id'],
                    telefono: $validated['telefono'] ?? null
                );
            } else {
                // Registrar voto para un inmueble específico
                $this->votacionService->registrarVoto(
                    preguntaId: $validated['pregunta_id'],
                    opcionId: $validated['opcion_id'],
                    inmuebleId: $validated['inmueble_id'],
                    telefono: $validated['telefono'] ?? null
                );
            }

            return response()->json([
                'message' => 'Voto(s) en proceso. Se procesará de forma asíncrona.',
                'status' => 'queued'
            ], 202); // 202 Accepted - request accepted for processing
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Ver voto específico
     * 
     * Obtiene los detalles de un voto específico.
     * 
     * @authenticated
     * 
     * @urlParam voto integer required ID del voto. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "pregunta_id": 1,
     *   "inmueble_id": 5,
     *   "opcion_id": 2,
     *   "coeficiente": 1.5,
     *   "telefono": "+573001234567",
     *   "votado_at": "2024-01-01T10:00:00Z"
     * }
     * 
     * @param \App\Models\Votaciones\Voto $voto
     * @return VotoResource
     */
    public function show(\App\Models\Votaciones\Voto $voto): VotoResource
    {
        $voto->load(['pregunta', 'inmueble', 'opcion']);
        
        return new VotoResource($voto);
    }
}
