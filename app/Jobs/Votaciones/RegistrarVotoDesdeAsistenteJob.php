<?php

namespace App\Jobs\Votaciones;

use App\Models\Asistentes\Asistente;
use App\Models\Votaciones\Pregunta;
use App\Models\Votaciones\Voto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para registrar votos desde un asistente.
 * 
 * Si el asistente representa múltiples inmuebles, se replicará el voto
 * para cada inmueble que el asistente representa.
 * 
 * Este job procesa el registro de múltiples votos de forma asíncrona,
 * mejorando el rendimiento y la capacidad de manejar alta concurrencia.
 */
class RegistrarVotoDesdeAsistenteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos máximos.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Tiempo de espera antes de reintentar (segundos).
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param int $preguntaId ID de la pregunta
     * @param int $opcionId ID de la opción seleccionada
     * @param int $asistenteId ID del asistente que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     */
    public function __construct(
        public int $preguntaId,
        public int $opcionId,
        public int $asistenteId,
        public ?string $telefono = null
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \RuntimeException Si la validación falla
     */
    public function handle(): void
    {
        $pregunta = Pregunta::findOrFail($this->preguntaId);

        // Validar que la pregunta esté abierta
        if (!$pregunta->estaAbierta()) {
            throw new \RuntimeException(
                "No se puede votar en una pregunta que no está abierta. " .
                "La pregunta está en estado '{$pregunta->estado}'."
            );
        }

        // Obtener el asistente y sus inmuebles
        $asistente = Asistente::findOrFail($this->asistenteId);
        $inmuebles = $asistente->inmuebles;

        if ($inmuebles->isEmpty()) {
            throw new \RuntimeException(
                "El asistente #{$this->asistenteId} no tiene inmuebles asociados."
            );
        }

        $votosRegistrados = [];

        // Replicar el voto para cada inmueble que el asistente representa
        DB::transaction(function () use (&$votosRegistrados, $inmuebles) {
            foreach ($inmuebles as $inmueble) {
                $lock = Cache::lock("voto:{$this->preguntaId}:{$inmueble->id}", 5);

                if (!$lock->get()) {
                    Log::warning('No se pudo obtener lock para voto replicado', [
                        'pregunta_id' => $this->preguntaId,
                        'inmueble_id' => $inmueble->id,
                    ]);
                    continue;
                }

                try {
                    // Verificar si el inmueble ya votó
                    $votoExistente = Voto::where('pregunta_id', $this->preguntaId)
                        ->where('inmueble_id', $inmueble->id)
                        ->first();

                    if ($votoExistente) {
                        continue;
                    }

                    // Registrar el voto para este inmueble
                    $voto = Voto::create([
                        'pregunta_id' => $this->preguntaId,
                        'inmueble_id' => $inmueble->id,
                        'opcion_id' => $this->opcionId,
                        'coeficiente' => $inmueble->coeficiente,
                        'telefono' => $this->telefono,
                        'votado_at' => now()->utc(),
                    ]);

                    $votosRegistrados[] = $voto;

                    Log::info('Voto registrado desde asistente (job)', [
                        'voto_id' => $voto->id,
                        'pregunta_id' => $this->preguntaId,
                        'asistente_id' => $this->asistenteId,
                        'inmueble_id' => $inmueble->id,
                        'opcion_id' => $this->opcionId,
                        'coeficiente' => $inmueble->coeficiente,
                    ]);
                } catch (QueryException $e) {
                    if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'UNIQUE')) {
                        continue;
                    }
                    throw $e;
                } finally {
                    optional($lock)->release();
                }
            }
        });

        if (empty($votosRegistrados)) {
            throw new \RuntimeException(
                "No se pudo registrar ningún voto. " .
                "Todos los inmuebles del asistente ya votaron en esta pregunta."
            );
        }
    }


    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Error al registrar votos desde asistente (job)', [
            'pregunta_id' => $this->preguntaId,
            'asistente_id' => $this->asistenteId,
            'opcion_id' => $this->opcionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
