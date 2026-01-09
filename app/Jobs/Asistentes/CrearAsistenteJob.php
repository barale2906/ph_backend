<?php

namespace App\Jobs\Asistentes;

use App\Models\Asistentes\Asistente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para crear un asistente.
 * 
 * Este job procesa la creación de un asistente de forma asíncrona,
 * mejorando el rendimiento y la capacidad de manejar alta concurrencia.
 */
class CrearAsistenteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos máximos.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tiempo de espera antes de reintentar (segundos).
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param array $datos Datos del asistente a crear
     */
    public function __construct(
        public array $datos
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Asistente::create($this->datos);
    }
}
