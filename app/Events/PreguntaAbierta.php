<?php

namespace App\Events;

use App\Models\Votaciones\Pregunta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se abre una pregunta para votación.
 * 
 * Este evento se usa para notificar al frontend (vía WebSockets)
 * que una pregunta ha sido abierta y está lista para recibir votos.
 * 
 * @property Pregunta $pregunta Pregunta que fue abierta
 */
class PreguntaAbierta
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Pregunta $pregunta Pregunta que fue abierta
     */
    public function __construct(
        public Pregunta $pregunta
    ) {
        //
    }
}
