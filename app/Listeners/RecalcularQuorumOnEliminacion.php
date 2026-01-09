<?php

namespace App\Listeners;

use App\Events\AsistenteEliminado;
use App\Services\QuorumService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Listener para recalcular el quórum cuando se elimina un asistente.
 * 
 * Este listener escucha el evento AsistenteEliminado
 * y recalcula automáticamente el quórum.
 */
class RecalcularQuorumOnEliminacion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @param QuorumService $quorumService Servicio de quórum
     */
    public function __construct(
        protected QuorumService $quorumService
    ) {
        //
    }

    /**
     * Handle el evento.
     *
     * @param AsistenteEliminado $event
     * @return void
     */
    public function handle(AsistenteEliminado $event): void
    {
        // Limpiar cache y recalcular quórum
        // El QuorumService emitirá el evento QuorumUpdated automáticamente
        $this->quorumService->limpiarCache();
        
        // Obtener todas las reuniones activas y recalcular quórum para cada una
        $reuniones = \App\Models\Reuniones\Reunion::where('estado', 'en_curso')
            ->orWhere('estado', 'iniciada')
            ->get();
        
        foreach ($reuniones as $reunion) {
            $this->quorumService->recalcular($reunion->id);
        }
    }
}
