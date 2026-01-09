<?php

namespace App\Http\Resources\Votaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Opcion.
 * 
 * Transforma el modelo Opcion en un formato JSON estructurado
 * para las respuestas de la API.
 */
class OpcionResource extends JsonResource
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
            'texto' => $this->texto,
            'orden' => $this->orden,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'votos_count' => $this->when(isset($this->votos_count), $this->votos_count),
        ];
    }
}
