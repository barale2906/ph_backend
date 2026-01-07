<?php

namespace App\Providers;

use App\Models\Ph;
use App\Models\User;
use App\Policies\PhPolicy;
use App\Policies\PoderPolicy;
use App\Policies\VotacionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ph::class => PhPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gates explícitos
        Gate::define('iniciar-asamblea', [VotacionPolicy::class, 'iniciarAsamblea']);
        Gate::define('cerrar-asamblea', [VotacionPolicy::class, 'cerrarAsamblea']);
        Gate::define('gestionar-votaciones', [VotacionPolicy::class, 'gestionarVotaciones']);
        Gate::define('registrar-poder', [PoderPolicy::class, 'registrar']);
        Gate::define('ver-poder', [PoderPolicy::class, 'view']);

        // Gate para verificar acceso a PH
        Gate::define('acceder-ph', function (User $user, int $phId) {
            return $user->tieneAccesoPh($phId);
        });
    }
}

