<?php

namespace App\Console\Commands;

use App\Models\Timers\Timer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para cerrar automáticamente los timers que han expirado.
 * 
 * Este comando debe ejecutarse periódicamente (cada minuto recomendado)
 * para cerrar automáticamente los timers que han alcanzado su tiempo límite.
 * 
 * IMPORTANTE: Solo el backend puede cerrar timers.
 * El cierre se basa en comparación UTC server-time.
 * 
 * Uso: php artisan timers:cerrar-expirados
 */
class CerrarTimersExpirados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timers:cerrar-expirados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra automáticamente los cronómetros que han expirado según el tiempo del servidor (UTC)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Buscando timers expirados...');

        // Buscar timers activos que han expirado
        $timersExpirados = Timer::where('estado', 'activo')
            ->whereNotNull('fin_at')
            ->where('fin_at', '<=', now()->utc())
            ->get();

        if ($timersExpirados->isEmpty()) {
            $this->info('No hay timers expirados.');
            return Command::SUCCESS;
        }

        $cerrados = 0;
        foreach ($timersExpirados as $timer) {
            try {
                // Verificar nuevamente que ha expirado (doble verificación)
                if ($timer->haExpirado()) {
                    $timer->finalizar();
                    $cerrados++;

                    // Log del evento
                    Log::info('Timer cerrado automáticamente', [
                        'timer_id' => $timer->id,
                        'reunion_id' => $timer->reunion_id,
                        'tipo' => $timer->tipo,
                        'cerrado_at' => now()->utc()->toIso8601String(),
                    ]);

                    $this->line("Timer #{$timer->id} ({$timer->tipo}) cerrado automáticamente.");
                }
            } catch (\Exception $e) {
                $this->error("Error al cerrar timer #{$timer->id}: {$e->getMessage()}");
                Log::error('Error al cerrar timer automáticamente', [
                    'timer_id' => $timer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Se cerraron {$cerrados} timer(s) automáticamente.");
        return Command::SUCCESS;
    }
}
