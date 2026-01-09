<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se actualiza el quórum.
 * 
 * Este evento se emite vía WebSockets para notificar al frontend
 * que el quórum ha sido recalculado y actualizado.
 * 
 * IMPORTANTE: Backend emite, frontend escucha.
 * 
 * @property int $reunionId ID de la reunión
 * @property array $quorum Datos del quórum actualizado
 */
class QuorumUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $reunionId ID de la reunión
     * @param array $quorum Datos del quórum actualizado
     */
    public function __construct(
        public int $reunionId,
        public array $quorum
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
        return [
            new PrivateChannel('reunion.' . $this->reunionId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'quorum.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'reunion_id' => $this->reunionId,
            'quorum' => $this->quorum,
            'timestamp' => now()->utc()->toIso8601String(),
        ];
    }
}
