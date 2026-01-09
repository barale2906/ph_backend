<?php

namespace App\Http\Resources\Votaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Voto.
 * 
 * Transforma el modelo Voto en un formato JSON estructurado
 * para las respuestas de la API.
 * 
 * IMPORTANTE: Los votos son inmutables, por lo que este resource
 * se usa principalmente para consultas y reportes.
 */
class VotoResource extends JsonResource
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
            'pregunta_id' => $this->pregunta_id,
            'inmueble_id' => $this->inmueble_id,
            'opcion_id' => $this->opcion_id,
            'coeficiente' => (float) $this->coeficiente,
            'telefono' => $this->telefono,
            'votado_at' => $this->votado_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'pregunta' => new PreguntaResource($this->whenLoaded('pregunta')),
            'inmueble' => $this->whenLoaded('inmueble'),
            'opcion' => new OpcionResource($this->whenLoaded('opcion')),
        ];
    }
}
