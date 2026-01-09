<?php

namespace App\Http\Resources\Votaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Pregunta.
 * 
 * Transforma el modelo Pregunta en un formato JSON estructurado
 * para las respuestas de la API.
 */
class PreguntaResource extends JsonResource
{
    /**
     * Transformar el resource en un array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reunion_id' => $this->reunion_id,
            'pregunta' => $this->pregunta,
            'estado' => $this->estado,
            'apertura_at' => $this->apertura_at?->toIso8601String(),
            'cierre_at' => $this->cierre_at?->toIso8601String(),
            'orden' => $this->orden,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'opciones' => OpcionResource::collection($this->whenLoaded('opciones')),
            'votos_count' => $this->when(isset($this->votos_count), $this->votos_count),
            'resultados' => $this->when(
                $request->boolean('incluir_resultados') && $this->estaCerrada(),
                fn() => $this->obtenerResultados()
            ),
        ];
    }
}
