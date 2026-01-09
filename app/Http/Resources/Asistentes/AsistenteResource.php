<?php

namespace App\Http\Resources\Asistentes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Asistente.
 * 
 * Transforma el modelo Asistente en un formato JSON estructurado
 * para las respuestas de la API.
 */
class AsistenteResource extends JsonResource
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
            'nombre' => $this->nombre,
            'documento' => $this->documento,
            'telefono' => $this->telefono,
            'codigo_acceso' => $this->codigo_acceso,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'inmuebles' => $this->whenLoaded('inmuebles', function () {
                return $this->inmuebles->map(function ($inmueble) {
                    return [
                        'id' => $inmueble->id,
                        'nomenclatura' => $inmueble->nomenclatura,
                        'coeficiente' => (float) $inmueble->coeficiente,
                        'tipo' => $inmueble->tipo,
                        'activo' => $inmueble->activo,
                        'pivot' => [
                            'coeficiente' => (float) $inmueble->pivot->coeficiente ?? $inmueble->coeficiente,
                            'poder_url' => $inmueble->pivot->poder_url ?? null,
                        ],
                    ];
                });
            }),
            'inmuebles_count' => $this->when(isset($this->inmuebles_count), $this->inmuebles_count),
        ];
    }
}
