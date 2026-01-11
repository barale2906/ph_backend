<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Crea un usuario SUPER_ADMIN inicial si no existe.
     * 
     * Por defecto:
     * - Email: admin@phsystem.com
     * - Password: admin123
     * 
     * Puedes cambiar estos valores usando variables de entorno:
     * - ADMIN_EMAIL
     * - ADMIN_PASSWORD
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@phsystem.com');
        $password = env('ADMIN_PASSWORD', 'admin123');
        $name = env('ADMIN_NAME', 'Administrador del Sistema');

        // Verificar si ya existe un usuario con este email
        if (User::where('email', $email)->exists()) {
            $this->command->warn("El usuario con email {$email} ya existe. Saltando creación.");
            return;
        }

        // Crear usuario SUPER_ADMIN
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'rol' => 'SUPER_ADMIN',
        ]);

        $this->command->info("✅ Usuario SUPER_ADMIN creado exitosamente:");
        $this->command->line("   Email: {$email}");
        $this->command->line("   Password: {$password}");
        $this->command->line("   Rol: SUPER_ADMIN");
        $this->command->warn("   ⚠️  IMPORTANTE: Cambia la contraseña después del primer login.");
    }
}
