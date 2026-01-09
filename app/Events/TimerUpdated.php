<?php

namespace App\Events;

use App\Models\Timers\Timer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se actualiza un cronómetro.
 * 
 * Este evento se emite vía WebSockets para notificar al frontend
 * sobre cambios en el estado del cronómetro (inicio, pausa, actualización de tiempo).
 * 
 * IMPORTANTE: Backend emite, frontend escucha.
 * 
 * @property Timer $timer Cronómetro actualizado
 */
class TimerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Timer $timer Cronómetro actualizado
     */
    public function __construct(
        public Timer $timer
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
            new PrivateChannel('reunion.' . $this->timer->reunion_id),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'timer.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'timer_id' => $this->timer->id,
            'reunion_id' => $this->timer->reunion_id,
            'tipo' => $this->timer->tipo,
            'estado' => $this->timer->estado,
            'duracion_segundos' => $this->timer->duracion_segundos,
            'inicio_at' => $this->timer->inicio_at?->toIso8601String(),
            'fin_at' => $this->timer->fin_at?->toIso8601String(),
            'tiempo_restante' => $this->timer->tiempoRestante(),
            'timestamp' => now()->utc()->toIso8601String(),
        ];
    }
}
