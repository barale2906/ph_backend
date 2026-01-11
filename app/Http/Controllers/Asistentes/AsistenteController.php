<?php

namespace App\Http\Controllers\Asistentes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistentes\StoreAsistenteRequest;
use App\Http\Requests\Asistentes\UpdateAsistenteRequest;
use App\Http\Resources\Asistentes\AsistenteResource;
use App\Models\Asistentes\Asistente;
use App\Models\Votaciones\Pregunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Asistentes
 * 
 * Controlador para la gestión de asistentes.
 * Permite crear, listar, ver, actualizar y eliminar asistentes.
 * 
 * IMPORTANTE: Requiere middleware 'tenant' para acceder a la base de datos de la PH.
 */
class AsistenteController extends Controller
{
    /**
     * Listar asistentes
     * 
     * Obtiene una lista de asistentes, opcionalmente filtrados.
     * 
     * @authenticated
     * 
     * @queryParam nombre string Filtrar por nombre. Example: Juan
     * @queryParam documento string Filtrar por documento. Example: 1234567890
     * @queryParam with_inmuebles boolean Incluir inmuebles en la respuesta. Example: true
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "nombre": "Juan Pérez",
     *       "documento": "1234567890",
     *       "telefono": "+573001234567",
     *       "codigo_acceso": "ABC12345"
     *     }
     *   ]
     * }
     * 
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Asistente::query()
            ->when(request('nombre'), fn($q, $nombre) => $q->where('nombre', 'like', "%{$nombre}%"))
            ->when(request('documento'), fn($q, $doc) => $q->where('documento', $doc))
            ->when(request('with_inmuebles'), fn($q) => $q->with('inmuebles'))
            ->orderBy('nombre');

        return AsistenteResource::collection($query->get());
    }

    /**
     * Crear nuevo asistente
     * 
     * Crea un nuevo asistente y lo asocia a uno o más inmuebles.
     * Solo ADMIN_PH y LOGISTICA pueden crear asistentes.
     * 
     * @authenticated
     * 
     * @bodyParam nombre string required Nombre completo del asistente. Example: Juan Pérez
     * @bodyParam documento string Número de documento (opcional). Example: 1234567890
     * @bodyParam telefono string Número de teléfono (opcional). Example: +573001234567
     * @bodyParam inmuebles array required Array de IDs de inmuebles. Example: [1, 2, 3]
     * 
     * @response 201 {
     *   "message": "Asistente creado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "nombre": "Juan Pérez",
     *     "codigo_acceso": "ABC12345"
     *   }
     * }
     * 
     * @param StoreAsistenteRequest $request
     * @return JsonResponse
     */
    public function store(StoreAsistenteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $inmuebles = $validated['inmuebles'];
        unset($validated['inmuebles']);

        if (array_key_exists('barcode_numero', $validated) && $this->votacionesEnCurso()) {
            abort(409, 'La asignación de códigos de barras solo es posible antes de iniciar las votaciones.');
        }

        $asistente = Asistente::create($validated);
        
        // Asociar inmuebles
        $asistente->inmuebles()->attach($inmuebles);

        return response()->json([
            'message' => 'Asistente creado exitosamente',
            'data' => new AsistenteResource($asistente->load('inmuebles'))
        ], 201);
    }

    /**
     * Ver asistente específico
     * 
     * Obtiene los detalles de un asistente específico.
     * 
     * @authenticated
     * 
     * @urlParam asistente integer required ID del asistente. Example: 1
     * 
     * @response 200 {
     *   "id": 1,
     *   "nombre": "Juan Pérez",
     *   "documento": "1234567890",
     *   "telefono": "+573001234567",
     *   "codigo_acceso": "ABC12345",
     *   "inmuebles": [...]
     * }
     * 
     * @param Asistente $asistente
     * @return AsistenteResource
     */
    public function show(Asistente $asistente): AsistenteResource
    {
        $asistente->load('inmuebles');
        
        return new AsistenteResource($asistente);
    }

    /**
     * Actualizar asistente
     * 
     * Actualiza los datos de un asistente existente.
     * Solo ADMIN_PH y LOGISTICA pueden actualizar asistentes.
     * 
     * @authenticated
     * 
     * @urlParam asistente integer required ID del asistente. Example: 1
     * @bodyParam nombre string Nombre completo del asistente. Example: Juan Pérez Actualizado
     * @bodyParam documento string Número de documento. Example: 1234567890
     * @bodyParam telefono string Número de teléfono. Example: +573001234567
     * @bodyParam inmuebles array Array de IDs de inmuebles. Example: [1, 2, 3]
     * 
     * @response 200 {
     *   "message": "Asistente actualizado exitosamente",
     *   "data": {...}
     * }
     * 
     * @param UpdateAsistenteRequest $request
     * @param Asistente $asistente
     * @return JsonResponse
     */
    public function update(UpdateAsistenteRequest $request, Asistente $asistente): JsonResponse
    {
        $validated = $request->validated();
        
        if (array_key_exists('barcode_numero', $validated) && $this->votacionesEnCurso()) {
            abort(409, 'La edición del código de barras solo es posible antes de iniciar las votaciones.');
        }

        if (isset($validated['inmuebles'])) {
            $inmuebles = $validated['inmuebles'];
            unset($validated['inmuebles']);
            $asistente->inmuebles()->sync($inmuebles);
        }
        
        $asistente->update($validated);

        return response()->json([
            'message' => 'Asistente actualizado exitosamente',
            'data' => new AsistenteResource($asistente->fresh()->load('inmuebles'))
        ]);
    }

    /**
     * Determina si ya existe una votación abierta en el tenant actual.
     */
    protected function votacionesEnCurso(): bool
    {
        return Pregunta::where('estado', 'abierta')->exists();
    }

    /**
     * Eliminar asistente
     * 
     * Elimina un asistente del sistema.
     * Solo ADMIN_PH puede eliminar asistentes.
     * 
     * @authenticated
     * 
     * @urlParam asistente integer required ID del asistente. Example: 1
     * 
     * @response 200 {
     *   "message": "Asistente eliminado exitosamente"
     * }
     * 
     * @param Asistente $asistente
     * @return JsonResponse
     */
    public function destroy(Asistente $asistente): JsonResponse
    {
        $asistente->delete();

        return response()->json([
            'message' => 'Asistente eliminado exitosamente'
        ]);
    }
}
