<?php

namespace App\Jobs\Votaciones;

use App\Models\Inmuebles\Inmueble;
use App\Models\Votaciones\Pregunta;
use App\Models\Votaciones\Voto;
use Illuminate\Database\QueryException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para registrar un voto en una pregunta.
 * 
 * Este job procesa el registro de un voto de forma asíncrona,
 * mejorando el rendimiento y la capacidad de manejar alta concurrencia.
 * 
 * IMPORTANTE: Los votos son inmutables una vez registrados.
 */
class RegistrarVotoJob implements ShouldQueue
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
     * @param int $inmuebleId ID del inmueble que vota
     * @param string|null $telefono Teléfono desde el cual se votó (opcional)
     */
    public function __construct(
        public int $preguntaId,
        public int $opcionId,
        public int $inmuebleId,
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
        $lock = Cache::lock("voto:{$this->preguntaId}:{$this->inmuebleId}", 5);

        if (!$lock->get()) {
            throw new \RuntimeException('No se pudo obtener el bloqueo para registrar el voto (concurrencia).');
        }

        try {
            DB::transaction(function () {
                $pregunta = Pregunta::findOrFail($this->preguntaId);

                // Validar que la pregunta esté abierta
                if (!$pregunta->estaAbierta()) {
                    throw new \RuntimeException(
                        "No se puede votar en una pregunta que no está abierta. " .
                        "La pregunta está en estado '{$pregunta->estado}'."
                    );
                }

                // Obtener el inmueble
                $inmueble = Inmueble::findOrFail($this->inmuebleId);

                // Verificar si el inmueble ya votó
                $votoExistente = Voto::where('pregunta_id', $this->preguntaId)
                    ->where('inmueble_id', $this->inmuebleId)
                    ->first();

                if ($votoExistente) {
                    throw new \RuntimeException(
                        "El inmueble #{$this->inmuebleId} ya votó en esta pregunta."
                    );
                }

                // Registrar el voto
                $voto = Voto::create([
                    'pregunta_id' => $this->preguntaId,
                    'inmueble_id' => $this->inmuebleId,
                    'opcion_id' => $this->opcionId,
                    'coeficiente' => $inmueble->coeficiente,
                    'telefono' => $this->telefono,
                    'votado_at' => now()->utc(),
                ]);

                Log::info('Voto registrado desde job', [
                    'voto_id' => $voto->id,
                    'pregunta_id' => $this->preguntaId,
                    'inmueble_id' => $this->inmuebleId,
                    'opcion_id' => $this->opcionId,
                    'coeficiente' => $inmueble->coeficiente,
                ]);
            });
        } catch (QueryException $e) {
            // Capturar violación de índice único como intento duplicado
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'UNIQUE')) {
                throw new \RuntimeException(
                    "El inmueble #{$this->inmuebleId} ya votó en esta pregunta."
                );
            }
            throw $e;
        } finally {
            optional($lock)->release();
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
        Log::error('Error al registrar voto desde job', [
            'pregunta_id' => $this->preguntaId,
            'inmueble_id' => $this->inmuebleId,
            'opcion_id' => $this->opcionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
