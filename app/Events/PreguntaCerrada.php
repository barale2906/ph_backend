<?php

namespace App\Events;

use App\Models\Votaciones\Pregunta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se cierra una pregunta para votación.
 * 
 * Este evento se usa para notificar al frontend (vía WebSockets)
 * que una pregunta ha sido cerrada y ya no acepta votos.
 * 
 * IMPORTANTE: Este evento solo se dispara cuando el backend cierra
 * la pregunta. El frontend NO puede cerrar preguntas.
 * 
 * @property Pregunta $pregunta Pregunta que fue cerrada
 */
class PreguntaCerrada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Pregunta $pregunta Pregunta que fue cerrada
     */
    public function __construct(
        public Pregunta $pregunta
    ) {
        //
    }
}
