<?php

namespace App\Events;

use App\Models\Timers\Timer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando finaliza un cronómetro.
 * 
 * Este evento dispara TimerUpdated para broadcasting.
 * 
 * IMPORTANTE: Este evento solo se dispara cuando el backend cierra
 * el cronómetro automáticamente. El frontend NO puede cerrar timers.
 * 
 * @property Timer $timer Cronómetro que finalizó
 */
class TimerEnded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Timer $timer Cronómetro que finalizó
     */
    public function __construct(
        public Timer $timer
    ) {
        // Disparar evento de broadcasting
        event(new TimerUpdated($timer));
    }
}
