<?php

namespace Database\Seeders;

use App\Models\Ph;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioPhSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ]);

        $adminPh = User::factory()->adminPh()->create([
            'name' => 'Admin PH',
            'email' => 'admin@example.com',
        ]);

        $logistica = User::factory()->logistica()->create([
            'name' => 'Logística',
            'email' => 'logistica@example.com',
        ]);

        $lectura = User::factory()->create([
            'name' => 'Usuario Lectura',
            'email' => 'lectura@example.com',
        ]);

        // Asociar usuarios con PHs
        $ph = Ph::first();
        if ($ph) {
            $ph->usuarios()->attach($adminPh->id, ['rol' => 'ADMIN_PH']);
            $ph->usuarios()->attach($logistica->id, ['rol' => 'LOGISTICA']);
            $ph->usuarios()->attach($lectura->id, ['rol' => 'LECTURA']);
        }
    }
}

