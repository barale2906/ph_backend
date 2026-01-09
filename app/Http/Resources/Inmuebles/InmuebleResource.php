<?php

namespace App\Http\Resources\Inmuebles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar el modelo Inmueble.
 * 
 * Transforma el modelo Inmueble en un formato JSON estructurado
 * para las respuestas de la API.
 */
class InmuebleResource extends JsonResource
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
            'nomenclatura' => $this->nomenclatura,
            'coeficiente' => (float) $this->coeficiente,
            'tipo' => $this->tipo,
            'propietario_documento' => $this->propietario_documento,
            'propietario_nombre' => $this->propietario_nombre,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => $this->activo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relaciones opcionales (cargar con ->with())
            'asistentes' => $this->whenLoaded('asistentes', function () {
                return $this->asistentes->map(function ($asistente) {
                    return [
                        'id' => $asistente->id,
                        'nombre' => $asistente->nombre,
                        'documento' => $asistente->documento,
                        'telefono' => $asistente->telefono,
                        'codigo_acceso' => $asistente->codigo_acceso,
                    ];
                });
            }),
            'asistentes_count' => $this->when(isset($this->asistentes_count), $this->asistentes_count),
            'votos_count' => $this->when(isset($this->votos_count), $this->votos_count),
        ];
    }
}
