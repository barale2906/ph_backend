<?php

namespace App\Listeners;

use App\Events\AsistenteRegistrado;
use App\Services\QuorumService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Listener para recalcular el quórum cuando se registra un asistente.
 * 
 * Este listener escucha el evento AsistenteRegistrado
 * y recalcula automáticamente el quórum.
 */
class RecalcularQuorumOnRegistro implements ShouldQueue
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
     * @param AsistenteRegistrado $event
     * @return void
     */
    public function handle(AsistenteRegistrado $event): void
    {
        // Limpiar cache y recalcular quórum
        // El QuorumService emitirá el evento QuorumUpdated automáticamente
        // si se proporciona reunionId. Por ahora recalculamos para todas.
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
