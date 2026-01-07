<?php

namespace Database\Factories;

use App\Models\AuditoriaEvento;
use App\Models\Ph;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditoriaEvento>
 */
class AuditoriaEventoFactory extends Factory
{
    protected $model = AuditoriaEvento::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventos = [
            'inicio_asamblea',
            'cierre_asamblea',
            'inicio_votacion',
            'cierre_votacion',
            'registro_asistente',
            'voto_recibido',
            'error_critico',
        ];

        $tipos = ['asamblea', 'votacion', 'asistencia', 'voto', 'sistema'];

        return [
            'evento' => $this->faker->randomElement($eventos),
            'tipo' => $this->faker->randomElement($tipos),
            'ph_id' => Ph::factory(),
            'usuario_id' => User::factory(),
            'reunion_id' => $this->faker->uuid(),
            'datos' => [
                'descripcion' => $this->faker->sentence(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}

