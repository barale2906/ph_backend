<?php

namespace App\Models\Votaciones;

use App\Events\PreguntaAbierta;
use App\Events\PreguntaCerrada;
use App\Models\Concerns\UsesPhDatabase;
use App\Models\Reuniones\Reunion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Pregunta (Votación).
 * 
 * Representa una pregunta o tema a votar en una reunión.
 * 
 * @property int $id Identificador único de la pregunta
 * @property int $reunion_id ID de la reunión
 * @property string $pregunta Texto de la pregunta/votación
 * @property string $estado Estado (abierta, cerrada, cancelada)
 * @property \Illuminate\Support\Carbon|null $apertura_at Momento de apertura
 * @property \Illuminate\Support\Carbon|null $cierre_at Momento de cierre
 * @property int $orden Orden en el orden del día
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Reunion $reunion Reunión a la que pertenece
 * @property-read \Illuminate\Database\Eloquent\Collection<Opcion> $opciones Opciones de respuesta
 * @property-read \Illuminate\Database\Eloquent\Collection<Voto> $votos Votos recibidos
 * @property-read OrdenDia|null $ordenDia Punto del orden del día asociado
 */
class Pregunta extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'preguntas';

    protected $fillable = [
        'reunion_id',
        'pregunta',
        'estado',
        'apertura_at',
        'cierre_at',
        'orden',
    ];

    protected $casts = [
        'apertura_at' => 'datetime',
        'cierre_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del modelo.
     * 
     * Valida que solo haya 1 pregunta ABIERTA por reunión.
     * Dispara eventos cuando se abre o cierra una pregunta.
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($pregunta) {
            // Validar que no haya otra pregunta abierta en la misma reunión
            if ($pregunta->estado === 'abierta') {
                static::validarUnicaPreguntaAbierta($pregunta);
            }
        });

        static::updating(function ($pregunta) {
            // Si se está abriendo, validar que no haya otra abierta
            if ($pregunta->isDirty('estado') && $pregunta->estado === 'abierta') {
                static::validarUnicaPreguntaAbierta($pregunta);
            }
        });
    }

    /**
     * Reunión a la que pertenece esta pregunta.
     * 
     * @return BelongsTo
     */
    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    /**
     * Opciones de respuesta para esta pregunta.
     * 
     * @return HasMany
     */
    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class, 'pregunta_id');
    }

    /**
     * Votos recibidos para esta pregunta.
     * 
     * @return HasMany
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'pregunta_id');
    }

    /**
     * Punto del orden del día asociado a esta pregunta.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ordenDia(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Reuniones\OrdenDia::class, 'pregunta_id');
    }

    /**
     * Verificar si la votación está abierta.
     * 
     * @return bool
     */
    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    /**
     * Abrir la votación.
     * 
     * IMPORTANTE: Solo el backend puede abrir preguntas.
     * Valida que no haya otra pregunta abierta en la reunión.
     * Dispara el evento PreguntaAbierta.
     * 
     * @return bool
     * @throws \RuntimeException Si ya existe una pregunta abierta en la reunión
     */
    public function abrir(): bool
    {
        // Validar que no haya otra pregunta abierta
        static::validarUnicaPreguntaAbierta($this);

        // Usar tiempo UTC del servidor
        $apertura = now()->utc();

        $actualizado = $this->update([
            'estado' => 'abierta',
            'apertura_at' => $apertura,
        ]);

        if ($actualizado) {
            // Disparar evento cuando se abre la pregunta
            event(new PreguntaAbierta($this->fresh()));
        }

        return $actualizado;
    }

    /**
     * Cerrar la votación.
     * 
     * IMPORTANTE: Solo el backend puede cerrar preguntas.
     * El frontend NO puede cerrar preguntas - el cierre es automático
     * o manual por el backend.
     * 
     * Dispara el evento PreguntaCerrada.
     * 
     * @return bool
     */
    public function cerrar(): bool
    {
        // Usar tiempo UTC del servidor para el cierre
        $cierre = now()->utc();

        $actualizado = $this->update([
            'estado' => 'cerrada',
            'cierre_at' => $cierre,
        ]);

        if ($actualizado) {
            // Disparar evento cuando se cierra la pregunta
            event(new PreguntaCerrada($this->fresh()));
        }

        return $actualizado;
    }

    /**
     * Verificar si la pregunta está cerrada.
     * 
     * @return bool
     */
    public function estaCerrada(): bool
    {
        return isset($this->estado) && $this->estado === 'cerrada';
    }

    /**
     * Verificar si la pregunta está cancelada.
     * 
     * @return bool
     */
    public function estaCancelada(): bool
    {
        return $this->estado === 'cancelada';
    }

    /**
     * Obtener los resultados de la votación.
     * 
     * Calcula los resultados agrupados por opción, incluyendo:
     * - Cantidad de votos por opción
     * - Suma de coeficientes por opción
     * - Porcentaje de votos y coeficientes
     * 
     * @return array<string, mixed>
     */
    public function obtenerResultados(): array
    {
        $totalVotos = $this->votos()->count();
        $totalCoeficientes = $this->votos()->sum('coeficiente');

        $resultados = [];
        foreach ($this->opciones as $opcion) {
            $votosOpcion = $opcion->votos()->count();
            $coeficientesOpcion = $opcion->votos()->sum('coeficiente');

            $resultados[] = [
                'opcion_id' => $opcion->id,
                'opcion_texto' => $opcion->texto,
                'votos_cantidad' => $votosOpcion,
                'votos_porcentaje' => $totalVotos > 0 ? ($votosOpcion / $totalVotos) * 100 : 0,
                'coeficientes_suma' => (float) $coeficientesOpcion,
                'coeficientes_porcentaje' => $totalCoeficientes > 0 ? ($coeficientesOpcion / $totalCoeficientes) * 100 : 0,
            ];
        }

        return [
            'pregunta_id' => $this->id,
            'pregunta_texto' => $this->pregunta,
            'total_votos' => $totalVotos,
            'total_coeficientes' => (float) $totalCoeficientes,
            'resultados' => $resultados,
            'calculado_at' => now()->utc()->toIso8601String(),
        ];
    }

    /**
     * Valida que solo haya 1 pregunta ABIERTA por reunión.
     * 
     * Regla crítica: Solo puede haber una pregunta abierta
     * en la misma reunión al mismo tiempo.
     * 
     * @param Pregunta $pregunta Pregunta a validar
     * @return void
     * @throws \RuntimeException Si ya existe una pregunta abierta en la reunión
     */
    protected static function validarUnicaPreguntaAbierta(Pregunta $pregunta): void
    {
        $preguntaAbierta = static::where('reunion_id', $pregunta->reunion_id)
            ->where('estado', 'abierta')
            ->where('id', '!=', $pregunta->id ?? 0)
            ->first();

        if ($preguntaAbierta) {
            throw new \RuntimeException(
                "Ya existe una pregunta ABIERTA para la reunión #{$pregunta->reunion_id}. " .
                "Solo puede haber 1 pregunta abierta por reunión. " .
                "Cierre la pregunta #{$preguntaAbierta->id} antes de abrir otra."
            );
        }
    }
}
