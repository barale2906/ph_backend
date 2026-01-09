<?php

namespace App\Events;

use App\Models\Timers\Timer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se inicia un cronómetro.
 * 
 * Este evento dispara TimerUpdated para broadcasting.
 * 
 * @property Timer $timer Cronómetro que fue iniciado
 */
class TimerStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Timer $timer Cronómetro que fue iniciado
     */
    public function __construct(
        public Timer $timer
    ) {
        // Disparar evento de broadcasting
        event(new TimerUpdated($timer));
    }
}
