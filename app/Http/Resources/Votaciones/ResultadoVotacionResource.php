<?php

namespace App\Http\Resources\Votaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serializar los resultados de una votación.
 * 
 * Este resource se usa específicamente para el endpoint de resultados.
 */
class ResultadoVotacionResource extends JsonResource
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
            'pregunta_id' => $this->resource['pregunta_id'] ?? null,
            'pregunta_texto' => $this->resource['pregunta_texto'] ?? null,
            'total_votos' => $this->resource['total_votos'] ?? 0,
            'total_coeficientes' => $this->resource['total_coeficientes'] ?? 0.0,
            'resultados' => $this->resource['resultados'] ?? [],
            'calculado_at' => $this->resource['calculado_at'] ?? null,
        ];
    }
}
