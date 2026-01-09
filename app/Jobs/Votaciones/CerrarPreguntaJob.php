<?php

namespace App\Jobs\Votaciones;

use App\Models\Votaciones\Pregunta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para cerrar una pregunta.
 * 
 * Este job procesa el cierre de una pregunta de forma asíncrona.
 * Solo el backend puede cerrar preguntas.
 */
class CerrarPreguntaJob implements ShouldQueue
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
     * @param int $preguntaId ID de la pregunta a cerrar
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
     */
    public function handle(): void
    {
        $pregunta = Pregunta::findOrFail($this->preguntaId);
        $pregunta->cerrar();
    }
}
