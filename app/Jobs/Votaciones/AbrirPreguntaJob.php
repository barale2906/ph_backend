<?php

namespace App\Jobs\Votaciones;

use App\Models\Votaciones\Pregunta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para abrir una pregunta para votación.
 * 
 * Este job procesa la apertura de una pregunta de forma asíncrona.
 * Solo puede haber una pregunta abierta por reunión.
 */
class AbrirPreguntaJob implements ShouldQueue
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
     * @param int $preguntaId ID de la pregunta a abrir
     */
    public function __construct(
        public int $preguntaId
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \RuntimeException Si ya existe una pregunta abierta en la reunión
     */
    public function handle(): void
    {
        $pregunta = Pregunta::findOrFail($this->preguntaId);
        $pregunta->abrir();
    }
}
