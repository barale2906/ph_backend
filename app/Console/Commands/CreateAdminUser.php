<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin 
                            {--email= : Email del administrador}
                            {--password= : Contraseña del administrador}
                            {--name= : Nombre del administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un usuario SUPER_ADMIN en el sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email del administrador', 'admin@phsystem.com');
        $password = $this->option('password') ?? $this->secret('Contraseña del administrador');
        $name = $this->option('name') ?? $this->ask('Nombre del administrador', 'Administrador del Sistema');

        // Validar datos
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
            'name' => $name,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->error('Errores de validación:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return Command::FAILURE;
        }

        // Verificar si ya existe
        if (User::where('email', $email)->exists()) {
            if (!$this->confirm("El usuario con email {$email} ya existe. ¿Deseas continuar de todas formas?")) {
                return Command::FAILURE;
            }
        }

        // Crear usuario
        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'rol' => 'SUPER_ADMIN',
        ]);

        $this->info("✅ Usuario SUPER_ADMIN creado exitosamente!");
        $this->line("   ID: {$admin->id}");
        $this->line("   Nombre: {$admin->name}");
        $this->line("   Email: {$admin->email}");
        $this->line("   Rol: {$admin->rol}");
        $this->warn("   ⚠️  IMPORTANTE: Guarda estas credenciales de forma segura.");

        return Command::SUCCESS;
    }
}
