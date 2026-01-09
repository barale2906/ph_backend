<?php

namespace App\Http\Controllers\Votaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Votaciones\StorePreguntaRequest;
use App\Http\Requests\Votaciones\UpdatePreguntaRequest;
use App\Http\Resources\Votaciones\PreguntaResource;
use App\Http\Resources\Votaciones\ResultadoVotacionResource;
use App\Models\Votaciones\Pregunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Votaciones - Preguntas
 * 
 * Controlador para la gestión de preguntas de votación.
 * Permite crear, listar, ver, actualizar y eliminar preguntas.
 * También permite abrir y cerrar preguntas para votación.
 */
class PreguntaController extends Controller
{
    /**
     * Listar preguntas
     * 
     * Obtiene una lista de preguntas, opcionalmente filtradas por reunión.
     * 
     * @authenticated
     * 
     * @queryParam reunion_id integer ID de la reunión para filtrar. Example: 1
     * @queryParam estado string Filtrar por estado (inactiva, abierta, cerrada, cancelada). Example: abierta
     * @queryParam incluir_resultados boolean Incluir resultados si la pregunta está cerrada. Example: false
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "reunion_id": 1,
     *       "pregunta": "¿Aprueba el presupuesto?",
     *       "estado": "abierta",
     *       "apertura_at": "2024-01-01T10:00:00Z",
     *       "cierre_at": null,
     *       "orden": 1
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Pregunta::query()
            ->with('opciones')
            ->when(request('reunion_id'), fn($q, $id) => $q->where('reunion_id', $id))
            ->when(request('estado'), fn($q, $estado) => $q->where('estado', $estado))
            ->orderBy('orden')
            ->orderBy('id');

        return PreguntaResource::collection($query->get());
    }

    /**
     * Crear nueva pregunta
     * 
     * Crea una nueva pregunta de votación en una reunión.
     * Solo ADMIN_PH y LOGISTICA pueden crear preguntas.
     * 
     * @authenticated
     * 
     * @bodyParam reunion_id integer required ID de la reunión. Example: 1
     * @bodyParam pregunta string required Texto de la pregunta. Example: ¿Aprueba el presupuesto?
     * @bodyParam estado string Estado inicial (inactiva, abierta, cerrada, cancelada). Default: inactiva. Example: inactiva
     * @bodyParam orden integer Orden en el orden del día. Example: 1
     * 
     * @response 201 {
     *   "message": "Pregunta creada exitosamente",
     *   "data": {
     *     "id": 1,
     *     "reunion_id": 1,
     *     "pregunta": "¿Aprueba el presupuesto?",
     *     "estado": "inactiva",
     *     "orden": 1
     *   }
     * }
     * 
     * @param StorePreguntaRequest $request
     * @return JsonResponse
     */
    public function store(StorePreguntaRequest $request): JsonResponse
    {
        $pregunta = Pregunta::create($request->validated());

        return response()->json([
            'message' => 'Pregunta creada exitosamente',
            'data' => new PreguntaResource($pregunta->load('opciones'))
        ], 201);
    }

    /**
     * Ver pregunta específica
     * 
     * Obtiene los detalles de una pregunta específica, incluyendo opciones y resultados si está cerrada.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * @queryParam incluir_resultados boolean Incluir resultados si la pregunta está cerrada. Example: true
     * 
     * @response 200 {
     *   "id": 1,
     *   "reunion_id": 1,
     *   "pregunta": "¿Aprueba el presupuesto?",
     *   "estado": "cerrada",
     *   "apertura_at": "2024-01-01T10:00:00Z",
     *   "cierre_at": "2024-01-01T10:30:00Z",
     *   "orden": 1,
     *   "opciones": [...],
     *   "resultados": {...}
     * }
     * 
     * @param Pregunta $pregunta
     * @return PreguntaResource
     */
    public function show(Pregunta $pregunta): PreguntaResource
    {
        $pregunta->load('opciones');
        
        return new PreguntaResource($pregunta);
    }

    /**
     * Actualizar pregunta
     * 
     * Actualiza los datos de una pregunta existente.
     * Solo ADMIN_PH y LOGISTICA pueden actualizar preguntas.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * @bodyParam pregunta string Texto de la pregunta. Example: ¿Aprueba el presupuesto actualizado?
     * @bodyParam estado string Estado (inactiva, abierta, cerrada, cancelada). Example: abierta
     * @bodyParam orden integer Orden en el orden del día. Example: 2
     * 
     * @response 200 {
     *   "message": "Pregunta actualizada exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdatePreguntaRequest $request
     * @param Pregunta $pregunta
     * @return JsonResponse
     */
    public function update(UpdatePreguntaRequest $request, Pregunta $pregunta): JsonResponse
    {
        $pregunta->update($request->validated());

        return response()->json([
            'message' => 'Pregunta actualizada exitosamente',
            'data' => new PreguntaResource($pregunta->fresh()->load('opciones'))
        ]);
    }

    /**
     * Eliminar pregunta
     * 
     * Elimina una pregunta del sistema.
     * Solo ADMIN_PH puede eliminar preguntas.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * 
     * @response 200 {
     *   "message": "Pregunta eliminada exitosamente"
     * }
     * 
     * @param Pregunta $pregunta
     * @return JsonResponse
     */
    public function destroy(Pregunta $pregunta): JsonResponse
    {
        // Solo se puede eliminar si no tiene votos
        if ($pregunta->votos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una pregunta que ya tiene votos registrados'
            ], 422);
        }

        $pregunta->delete();

        return response()->json([
            'message' => 'Pregunta eliminada exitosamente'
        ]);
    }

    /**
     * Abrir pregunta para votación
     * 
     * Abre una pregunta para que los asistentes puedan votar.
     * Solo puede haber una pregunta abierta por reunión.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * 
     * @response 200 {
     *   "message": "Pregunta abierta exitosamente",
     *   "data": {...}
     * }
     * 
     * @response 422 {
     *   "message": "Ya existe una pregunta ABIERTA para esta reunión"
     * }
     * 
     * @param Pregunta $pregunta
     * @return JsonResponse
     */
    public function abrir(Pregunta $pregunta): JsonResponse
    {
        try {
            // En FASE 7, todas las escrituras van por cola
            \App\Jobs\Votaciones\AbrirPreguntaJob::dispatch($pregunta->id);

            return response()->json([
                'message' => 'Pregunta en proceso de apertura. Se procesará de forma asíncrona.',
                'status' => 'queued'
            ], 202); // 202 Accepted
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cerrar pregunta
     * 
     * Cierra una pregunta para que ya no acepte votos.
     * Solo el backend puede cerrar preguntas.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * 
     * @response 200 {
     *   "message": "Pregunta cerrada exitosamente",
     *   "data": {...},
     *   "resultados": {...}
     * }
     * 
     * @param Pregunta $pregunta
     * @return JsonResponse
     */
    public function cerrar(Pregunta $pregunta): JsonResponse
    {
        // En FASE 7, todas las escrituras van por cola
        \App\Jobs\Votaciones\CerrarPreguntaJob::dispatch($pregunta->id);

        return response()->json([
            'message' => 'Pregunta en proceso de cierre. Se procesará de forma asíncrona.',
            'status' => 'queued'
        ], 202); // 202 Accepted
    }

    /**
     * Obtener resultados de votación
     * 
     * Obtiene los resultados detallados de una pregunta cerrada.
     * 
     * @authenticated
     * 
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * 
     * @response 200 {
     *   "pregunta_id": 1,
     *   "pregunta_texto": "¿Aprueba el presupuesto?",
     *   "total_votos": 45,
     *   "total_coeficientes": 67.50,
     *   "resultados": [
     *     {
     *       "opcion_id": 1,
     *       "opcion_texto": "Sí",
     *       "votos_cantidad": 30,
     *       "votos_porcentaje": 66.67,
     *       "coeficientes_suma": 45.50,
     *       "coeficientes_porcentaje": 67.41
     *     }
     *   ],
     *   "calculado_at": "2024-01-01T10:30:00Z"
     * }
     * 
     * @response 422 {
     *   "message": "Solo se pueden obtener resultados de preguntas cerradas"
     * }
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function resultados(Pregunta $pregunta): JsonResponse
    {
        // Verificar estado sin hacer queries complejas
        if ($pregunta->estado !== 'cerrada') {
            return response()->json([
                'message' => 'Solo se pueden obtener resultados de preguntas cerradas'
            ], 422);
        }

        // Cargar relaciones necesarias antes de obtener resultados
        $pregunta->load(['opciones', 'votos']);
        
        $resultados = $pregunta->obtenerResultados();
        
        return (new ResultadoVotacionResource($resultados))->response();
    }
}
