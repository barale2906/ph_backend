<?php

namespace App\Events;

use App\Models\Asistentes\Asistente;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se elimina un asistente.
 * 
 * Este evento se usa para recalcular el quórum automáticamente
 * cuando un asistente es eliminado de la reunión.
 * 
 * @property Asistente $asistente Asistente que fue eliminado
 * @property array $inmueblesIds IDs de los inmuebles que representaba
 */
class AsistenteEliminado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Asistente $asistente Asistente que fue eliminado
     * @param array $inmueblesIds IDs de los inmuebles que representaba
     */
    public function __construct(
        public Asistente $asistente,
        public array $inmueblesIds = []
    ) {
        //
    }
}
