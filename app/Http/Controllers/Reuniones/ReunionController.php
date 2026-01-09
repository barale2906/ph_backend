<?php

namespace App\Http\Controllers\Reuniones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reuniones\StoreReunionRequest;
use App\Http\Requests\Reuniones\UpdateReunionRequest;
use App\Http\Resources\Reuniones\ReunionResource;
use App\Models\Reuniones\Reunion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Reuniones
 * 
 * Controlador para la gestión de reuniones (asambleas).
 * Permite crear, listar, ver, actualizar y eliminar reuniones.
 * 
 * IMPORTANTE: Requiere middleware 'tenant' para acceder a la base de datos de la PH.
 */
class ReunionController extends Controller
{
    /**
     * Listar reuniones
     * 
     * Obtiene una lista de reuniones, opcionalmente filtradas por estado o tipo.
     * 
     * @authenticated
     * 
     * @queryParam estado string Filtrar por estado (programada, en_curso, finalizada, cancelada). Example: en_curso
     * @queryParam tipo string Filtrar por tipo (ordinaria, extraordinaria). Example: ordinaria
     * @queryParam with_preguntas boolean Incluir preguntas en la respuesta. Example: false
     * @queryParam with_timers boolean Incluir timers en la respuesta. Example: false
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "tipo": "ordinaria",
     *       "fecha": "2024-01-15",
     *       "hora": "14:00",
     *       "modalidad": "presencial",
     *       "estado": "programada"
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Reunion::query()
            ->when(request('estado'), fn($q, $estado) => $q->where('estado', $estado))
            ->when(request('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when(request('with_preguntas'), fn($q) => $q->with('preguntas'))
            ->when(request('with_timers'), fn($q) => $q->with('timers'))
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc');

        return ReunionResource::collection($query->get());
    }

    /**
     * Crear nueva reunión
     * 
     * Crea una nueva reunión (asamblea) en la PH.
     * Solo ADMIN_PH puede crear reuniones.
     * 
     * @authenticated
     * 
     * @bodyParam tipo string required Tipo de reunión (ordinaria, extraordinaria). Example: ordinaria
     * @bodyParam fecha string required Fecha de la reunión (formato: YYYY-MM-DD). Example: 2024-01-15
     * @bodyParam hora string required Hora de inicio (formato: HH:MM). Example: 14:00
     * @bodyParam modalidad string required Modalidad (presencial, virtual, mixta). Example: presencial
     * @bodyParam estado string Estado inicial (programada, en_curso, finalizada, cancelada). Default: programada. Example: programada
     * 
     * @response 201 {
     *   "message": "Reunión creada exitosamente",
     *   "data": {
     *     "id": 1,
     *     "tipo": "ordinaria",
     *     "fecha": "2024-01-15",
     *     "hora": "14:00",
     *     "modalidad": "presencial",
     *     "estado": "programada"
     *   }
     * }
     * 
     * @param StoreReunionRequest $request
     * @return JsonResponse
     */
    public function store(StoreReunionRequest $request): JsonResponse
    {
        $reunion = Reunion::create($request->validated());

        return response()->json([
            'message' => 'Reunión creada exitosamente',
            'data' => new ReunionResource($reunion)
        ], 201);
    }

    /**
     * Ver reunión específica
     * 
     * Obtiene los detalles de una reunión específica.
     * 
     * @authenticated
     * 
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "tipo": "ordinaria",
     *   "fecha": "2024-01-15",
     *   "hora": "14:00",
     *   "modalidad": "presencial",
     *   "estado": "en_curso",
     *   "preguntas": [...],
     *   "timers": [...]
     * }
     * 
     * @param Reunion $reunion
     * @return ReunionResource
     */
    public function show(Reunion $reunion): ReunionResource
    {
        $reunion->load(['preguntas.opciones', 'timers']);
        
        return new ReunionResource($reunion);
    }

    /**
     * Actualizar reunión
     * 
     * Actualiza los datos de una reunión existente.
     * Solo ADMIN_PH puede actualizar reuniones.
     * 
     * @authenticated
     * 
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @bodyParam tipo string Tipo de reunión (ordinaria, extraordinaria). Example: extraordinaria
     * @bodyParam fecha string Fecha de la reunión (formato: YYYY-MM-DD). Example: 2024-01-20
     * @bodyParam hora string Hora de inicio (formato: HH:MM). Example: 15:00
     * @bodyParam modalidad string Modalidad (presencial, virtual, mixta). Example: virtual
     * @bodyParam estado string Estado (programada, en_curso, finalizada, cancelada). Example: en_curso
     * 
     * @response 200 {
     *   "message": "Reunión actualizada exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdateReunionRequest $request
     * @param Reunion $reunion
     * @return JsonResponse
     */
    public function update(UpdateReunionRequest $request, Reunion $reunion): JsonResponse
    {
        $reunion->update($request->validated());

        return response()->json([
            'message' => 'Reunión actualizada exitosamente',
            'data' => new ReunionResource($reunion->fresh())
        ]);
    }

    /**
     * Eliminar reunión
     * 
     * Elimina una reunión del sistema.
     * Solo ADMIN_PH puede eliminar reuniones.
     * 
     * @authenticated
     * 
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * 
     * @response 200 {
     *   "message": "Reunión eliminada exitosamente"
     * }
     * 
     * @param Reunion $reunion
     * @return JsonResponse
     */
    public function destroy(Reunion $reunion): JsonResponse
    {
        // No se puede eliminar si tiene preguntas o timers
        if ($reunion->preguntas()->exists() || $reunion->timers()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una reunión que tiene preguntas o timers asociados'
            ], 422);
        }

        $reunion->delete();

        return response()->json([
            'message' => 'Reunión eliminada exitosamente'
        ]);
    }

    /**
     * Iniciar reunión
     * 
     * Inicia una reunión, cambiando su estado a 'en_curso'.
     * Solo ADMIN_PH puede iniciar reuniones.
     * 
     * @authenticated
     * 
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * 
     * @response 200 {
     *   "message": "Reunión iniciada exitosamente",
     *   "data": {...}
     * }
     * 
     * @param Reunion $reunion
     * @return JsonResponse
     */
    public function iniciar(Reunion $reunion): JsonResponse
    {
        if ($reunion->estaEnCurso()) {
            return response()->json([
                'message' => 'La reunión ya está en curso'
            ], 422);
        }

        $reunion->iniciar();

        return response()->json([
            'message' => 'Reunión iniciada exitosamente',
            'data' => new ReunionResource($reunion->fresh())
        ]);
    }

    /**
     * Cerrar reunión
     * 
     * Cierra una reunión, cambiando su estado a 'finalizada'.
     * Solo ADMIN_PH puede cerrar reuniones.
     * 
     * @authenticated
     * 
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * 
     * @response 200 {
     *   "message": "Reunión cerrada exitosamente",
     *   "data": {...}
     * }
     * 
     * @param Reunion $reunion
     * @return JsonResponse
     */
    public function cerrar(Reunion $reunion): JsonResponse
    {
        if ($reunion->estaFinalizada()) {
            return response()->json([
                'message' => 'La reunión ya está finalizada'
            ], 422);
        }

        $reunion->cerrar();

        return response()->json([
            'message' => 'Reunión cerrada exitosamente',
            'data' => new ReunionResource($reunion->fresh())
        ]);
    }
}
