<?php

namespace App\Providers;

use App\Events\AsistenteEliminado;
use App\Events\AsistenteRegistrado;
use App\Listeners\RecalcularQuorumOnEliminacion;
use App\Listeners\RecalcularQuorumOnRegistro;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Service Provider para registrar eventos y listeners de la aplicación.
 * 
 * Registra los eventos relacionados con el quórum y sus listeners.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de eventos a listeners.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AsistenteRegistrado::class => [
            RecalcularQuorumOnRegistro::class,
        ],
        AsistenteEliminado::class => [
            RecalcularQuorumOnEliminacion::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
