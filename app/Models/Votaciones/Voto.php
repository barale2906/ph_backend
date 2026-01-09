<?php

namespace App\Models\Votaciones;

use App\Events\VotoRegistrado;
use App\Models\Concerns\UsesPhDatabase;
use App\Models\Inmuebles\Inmueble;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Voto.
 * 
 * Representa un voto emitido por un inmueble en una pregunta.
 * IMPORTANTE: Los votos son INMUTABLES (no se pueden actualizar ni eliminar).
 * 
 * @property int $id Identificador único del voto
 * @property int $pregunta_id ID de la pregunta
 * @property int $inmueble_id ID del inmueble que votó
 * @property int $opcion_id ID de la opción seleccionada
 * @property float $coeficiente Coeficiente del inmueble al momento del voto
 * @property string|null $telefono Teléfono desde el cual se votó (WhatsApp)
 * @property \Illuminate\Support\Carbon $votado_at Momento exacto del voto
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Pregunta $pregunta Pregunta por la cual se votó
 * @property-read Inmueble $inmueble Inmueble que votó
 * @property-read Opcion $opcion Opción seleccionada
 */
class Voto extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'votos';

    protected $fillable = [
        'pregunta_id',
        'inmueble_id',
        'opcion_id',
        'coeficiente',
        'telefono',
        'votado_at',
    ];

    protected $casts = [
        'coeficiente' => 'decimal:2',
        'votado_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del modelo.
     * 
     * Previene actualizaciones y eliminaciones de votos.
     * Valida que un inmueble solo vote una vez por pregunta.
     * Dispara evento cuando se registra un voto.
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Validar que un inmueble solo vote una vez por pregunta
        static::creating(function ($voto) {
            static::validarVotoUnico($voto);
            
            // Establecer tiempo UTC del servidor
            if (!$voto->votado_at) {
                $voto->votado_at = now()->utc();
            }
        });

        // Prevenir actualizaciones
        static::updating(function () {
            throw new \RuntimeException('Los votos son inmutables y no pueden ser actualizados.');
        });

        // Prevenir eliminaciones
        static::deleting(function () {
            throw new \RuntimeException('Los votos son inmutables y no pueden ser eliminados.');
        });

        // Disparar evento cuando se crea un voto
        static::created(function ($voto) {
            event(new VotoRegistrado($voto));
        });
    }

    /**
     * Pregunta por la cual se votó.
     * 
     * @return BelongsTo
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    /**
     * Inmueble que votó.
     * 
     * @return BelongsTo
     */
    public function inmueble(): BelongsTo
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }

    /**
     * Opción seleccionada.
     * 
     * @return BelongsTo
     */
    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class, 'opcion_id');
    }

    /**
     * Valida que un inmueble solo vote una vez por pregunta.
     * 
     * Regla crítica: Un inmueble solo puede emitir un voto por pregunta.
     * La validación se hace a nivel de base de datos (unique constraint)
     * y también a nivel de aplicación.
     * 
     * @param Voto $voto Voto a validar
     * @return void
     * @throws \RuntimeException Si el inmueble ya votó en esta pregunta
     */
    protected static function validarVotoUnico(Voto $voto): void
    {
        // Verificar que la pregunta esté abierta
        $pregunta = Pregunta::find($voto->pregunta_id);
        if (!$pregunta || !$pregunta->estaAbierta()) {
            $estado = $pregunta ? $pregunta->estado : 'inexistente';
            throw new \RuntimeException(
                "No se puede votar en una pregunta que no está abierta. " .
                "La pregunta #{$voto->pregunta_id} está en estado '{$estado}'."
            );
        }

        // Verificar que el inmueble no haya votado ya
        $votoExistente = static::where('pregunta_id', $voto->pregunta_id)
            ->where('inmueble_id', $voto->inmueble_id)
            ->first();

        if ($votoExistente) {
            throw new \RuntimeException(
                "El inmueble #{$voto->inmueble_id} ya votó en la pregunta #{$voto->pregunta_id}. " .
                "Un inmueble solo puede votar una vez por pregunta."
            );
        }
    }
}
