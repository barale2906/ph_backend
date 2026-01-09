<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para cerrar automáticamente los timers expirados.
 * 
 * Este job ejecuta el comando CerrarTimersExpirados en segundo plano.
 * Se puede programar para ejecutarse periódicamente (cada minuto recomendado).
 * 
 * IMPORTANTE: Solo el backend puede cerrar timers.
 */
class CerrarTimersExpiradosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ejecutar el comando usando Artisan
        \Illuminate\Support\Facades\Artisan::call('timers:cerrar-expirados');
    }
}
