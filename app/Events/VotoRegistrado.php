<?php

namespace App\Events;

use App\Models\Votaciones\Voto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se registra un voto.
 * 
 * Este evento se emite vía WebSockets para notificar al frontend
 * que se ha registrado un nuevo voto, permitiendo actualizar
 * los resultados en tiempo real.
 * 
 * IMPORTANTE: Backend emite, frontend escucha.
 * 
 * @property Voto $voto Voto que fue registrado
 */
class VotoRegistrado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Voto $voto Voto que fue registrado
     */
    public function __construct(
        public Voto $voto
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Obtener el reunion_id desde la pregunta del voto
        $reunionId = $this->voto->pregunta->reunion_id;
        
        return [
            new PrivateChannel('reunion.' . $reunionId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'vote.registered';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'voto_id' => $this->voto->id,
            'pregunta_id' => $this->voto->pregunta_id,
            'inmueble_id' => $this->voto->inmueble_id,
            'opcion_id' => $this->voto->opcion_id,
            'coeficiente' => (float) $this->voto->coeficiente,
            'votado_at' => $this->voto->votado_at?->toIso8601String(),
            'timestamp' => now()->utc()->toIso8601String(),
        ];
    }
}
