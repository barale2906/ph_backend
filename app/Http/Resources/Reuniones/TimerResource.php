<?php

namespace App\Http\Resources\Reuniones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Timer en el contexto de Reuniones.
 */
class TimerResource extends JsonResource
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
            'tipo' => $this->tipo,
            'duracion_segundos' => $this->duracion_segundos,
            'estado' => $this->estado,
            'inicio_at' => $this->inicio_at?->toIso8601String(),
            'fin_at' => $this->fin_at?->toIso8601String(),
            'tiempo_restante' => $this->tiempoRestante(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
