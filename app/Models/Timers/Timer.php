<?php

namespace App\Models\Timers;

use App\Events\TimerEnded;
use App\Events\TimerStarted;
use App\Models\Concerns\UsesPhDatabase;
use App\Models\Reuniones\Reunion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Timer (Cronómetro).
 * 
 * Representa un cronómetro para intervenciones o votaciones en una reunión.
 * IMPORTANTE: Solo puede haber 1 timer ACTIVO por tipo y reunión.
 * El backend cierra automáticamente los timers cuando expiran.
 * 
 * @property int $id Identificador único del cronómetro
 * @property int $reunion_id ID de la reunión
 * @property string $tipo Tipo (INTERVENCION | VOTACION)
 * @property int $duracion_segundos Duración en segundos
 * @property \Illuminate\Support\Carbon|null $inicio_at Momento de inicio real
 * @property \Illuminate\Support\Carbon|null $fin_at Momento de fin calculado o real
 * @property string $estado Estado (inactivo, activo, pausado, finalizado)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Reunion $reunion Reunión a la que pertenece
 */
class Timer extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'timers';

    protected $fillable = [
        'reunion_id',
        'tipo',
        'duracion_segundos',
        'inicio_at',
        'fin_at',
        'estado',
    ];

    protected $casts = [
        'inicio_at' => 'datetime',
        'fin_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del modelo.
     * 
     * Valida que solo haya 1 timer ACTIVO por tipo y reunión.
     * Dispara eventos cuando se inicia o finaliza un timer.
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($timer) {
            // Validar que no haya otro timer activo del mismo tipo en la misma reunión
            static::validarUnicoTimerActivo($timer);
        });

        static::updating(function ($timer) {
            // Si se está activando, validar que no haya otro activo
            if ($timer->isDirty('estado') && $timer->estado === 'activo') {
                static::validarUnicoTimerActivo($timer);
            }
        });
    }

    /**
     * Reunión a la que pertenece este cronómetro.
     * 
     * @return BelongsTo
     */
    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    /**
     * Verificar si el timer está activo.
     * 
     * @return bool
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Verificar si el timer está finalizado.
     * 
     * @return bool
     */
    public function estaFinalizado(): bool
    {
        return $this->estado === 'finalizado';
    }

    /**
     * Iniciar el cronómetro.
     * 
     * IMPORTANTE: Solo el backend puede iniciar timers.
     * Valida que no haya otro timer activo del mismo tipo en la reunión.
     * Dispara el evento TimerStarted.
     * 
     * @return bool
     * @throws \RuntimeException Si ya existe un timer activo del mismo tipo
     */
    public function iniciar(): bool
    {
        // Validar que no haya otro timer activo del mismo tipo
        static::validarUnicoTimerActivo($this);

        // Usar tiempo UTC del servidor
        $inicio = now()->utc();
        $fin = $inicio->copy()->addSeconds($this->duracion_segundos);

        $actualizado = $this->update([
            'estado' => 'activo',
            'inicio_at' => $inicio,
            'fin_at' => $fin,
        ]);

        if ($actualizado) {
            // Disparar evento cuando se inicia el timer
            // TimerStarted disparará TimerUpdated automáticamente
            event(new TimerStarted($this->fresh()));
        }

        return $actualizado;
    }

    /**
     * Pausar el cronómetro.
     * 
     * @return bool
     */
    public function pausar(): bool
    {
        $actualizado = $this->update(['estado' => 'pausado']);
        
        if ($actualizado) {
            // Disparar evento de broadcasting
            event(new \App\Events\TimerUpdated($this->fresh()));
        }
        
        return $actualizado;
    }

    /**
     * Finalizar el cronómetro (llamado automáticamente por el backend).
     * 
     * IMPORTANTE: Solo el backend puede cerrar timers.
     * El frontend NO puede enviar "fin" - el cierre es automático
     * basado en el tiempo del servidor (UTC).
     * 
     * Dispara el evento TimerEnded.
     * 
     * @return bool
     */
    public function finalizar(): bool
    {
        // Usar tiempo UTC del servidor para el cierre
        $fin = now()->utc();

        $actualizado = $this->update([
            'estado' => 'finalizado',
            'fin_at' => $fin,
        ]);

        if ($actualizado) {
            // Disparar evento cuando finaliza el timer
            // TimerEnded disparará TimerUpdated automáticamente
            event(new TimerEnded($this->fresh()));
        }

        return $actualizado;
    }

    /**
     * Obtener el tiempo restante en segundos.
     * 
     * Calcula basado en tiempo UTC del servidor.
     * 
     * @return int|null
     */
    public function tiempoRestante(): ?int
    {
        if (!$this->estaActivo() || !$this->fin_at) {
            return null;
        }

        // Usar tiempo UTC del servidor para comparación
        $ahora = now()->utc();
        $restante = $ahora->diffInSeconds($this->fin_at, false);
        return $restante > 0 ? $restante : 0;
    }

    /**
     * Verificar si el timer ha expirado según el tiempo del servidor.
     * 
     * Compara el tiempo UTC del servidor con fin_at.
     * 
     * @return bool
     */
    public function haExpirado(): bool
    {
        if (!$this->estaActivo() || !$this->fin_at) {
            return false;
        }

        // Comparación UTC server-time
        return now()->utc()->greaterThanOrEqualTo($this->fin_at);
    }

    /**
     * Valida que solo haya 1 timer ACTIVO por tipo y reunión.
     * 
     * Regla crítica: Solo puede haber un timer activo del mismo tipo
     * en la misma reunión al mismo tiempo.
     * 
     * @param Timer $timer Timer a validar
     * @return void
     * @throws \RuntimeException Si ya existe un timer activo del mismo tipo
     */
    protected static function validarUnicoTimerActivo(Timer $timer): void
    {
        $timerActivo = static::where('reunion_id', $timer->reunion_id)
            ->where('tipo', $timer->tipo)
            ->where('estado', 'activo')
            ->where('id', '!=', $timer->id ?? 0)
            ->first();

        if ($timerActivo) {
            throw new \RuntimeException(
                "Ya existe un cronómetro ACTIVO de tipo '{$timer->tipo}' " .
                "para la reunión #{$timer->reunion_id}. " .
                "Solo puede haber 1 timer activo por tipo y reunión."
            );
        }
    }
}
