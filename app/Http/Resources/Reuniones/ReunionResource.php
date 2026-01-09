<?php

namespace App\Http\Resources\Reuniones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Reunion.
 * 
 * Transforma el modelo Reunion en un formato JSON estructurado
 * para las respuestas de la API.
 */
class ReunionResource extends JsonResource
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
            'tipo' => $this->tipo,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'hora' => $this->hora,
            'modalidad' => $this->modalidad,
            'estado' => $this->estado,
            'inicio_at' => $this->inicio_at?->toIso8601String(),
            'cierre_at' => $this->cierre_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'preguntas' => \App\Http\Resources\Votaciones\PreguntaResource::collection($this->whenLoaded('preguntas')),
            'preguntas_count' => $this->when(isset($this->preguntas_count), $this->preguntas_count),
            'timers' => TimerResource::collection($this->whenLoaded('timers')),
            'timers_count' => $this->when(isset($this->timers_count), $this->timers_count),
        ];
    }
}
