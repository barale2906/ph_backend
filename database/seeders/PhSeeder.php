<?php

namespace Database\Seeders;

use App\Models\Ph;
use Illuminate\Database\Seeder;

class PhSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ph::factory()->create([
            'nit' => '900123456',
            'nombre' => 'PH Ejemplo',
            'db_name' => 'ph_ejemplo_900123456',
            'estado' => 'activo',
        ]);

        Ph::factory(5)->create();
    }
}

