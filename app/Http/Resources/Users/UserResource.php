<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'phs' => $this->whenLoaded('phs', function () {
                return $this->phs->map(function ($ph) {
                    return [
                        'id' => $ph->id,
                        'nit' => $ph->nit,
                        'nombre' => $ph->nombre,
                        'rol' => $ph->pivot->rol ?? null,
                    ];
                });
            }, []),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
