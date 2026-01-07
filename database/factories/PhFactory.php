<?php

namespace Database\Factories;

use App\Models\Ph;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ph>
 */
class PhFactory extends Factory
{
    protected $model = Ph::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nit = $this->faker->unique()->numerify('##########');
        $dbName = 'ph_' . strtolower($this->faker->unique()->word()) . '_' . $nit;

        return [
            'nit' => $nit,
            'nombre' => $this->faker->company() . ' PH',
            'db_name' => $dbName,
            'estado' => 'activo',
        ];
    }

    /**
     * Indicate that the PH is inactive.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }
}

