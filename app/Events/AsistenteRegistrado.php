<?php

namespace App\Events;

use App\Models\Asistentes\Asistente;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se registra un asistente.
 * 
 * Este evento se usa para recalcular el quórum automáticamente
 * cuando un nuevo asistente se registra en la reunión.
 * 
 * @property Asistente $asistente Asistente que fue registrado
 */
class AsistenteRegistrado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Asistente $asistente Asistente que fue registrado
     */
    public function __construct(
        public Asistente $asistente
    ) {
        //
    }
}
