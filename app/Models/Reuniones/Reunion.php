<?php

namespace App\Models\Reuniones;

use App\Models\Concerns\UsesPhDatabase;
use App\Models\Timers\Timer;
use App\Models\Votaciones\Pregunta;
use App\Models\Reuniones\OrdenDia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Reunión (Asamblea).
 * 
 * Representa una reunión o asamblea de la Propiedad Horizontal.
 * Puede ser ordinaria o extraordinaria, presencial, virtual o mixta.
 * 
 * @property int $id Identificador único de la reunión
 * @property string $tipo Tipo de reunión (ordinaria, extraordinaria)
 * @property \Illuminate\Support\Carbon $fecha Fecha programada de la reunión
 * @property string $hora Hora programada de inicio
 * @property string $modalidad Modalidad (presencial, virtual, mixta)
 * @property string $estado Estado actual (programada, en_curso, finalizada, cancelada)
 * @property \Illuminate\Support\Carbon|null $inicio_at Momento real de inicio
 * @property \Illuminate\Support\Carbon|null $cierre_at Momento real de cierre
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Pregunta> $preguntas Preguntas/votaciones de esta reunión
 * @property-read \Illuminate\Database\Eloquent\Collection<OrdenDia> $ordenDia Puntos del orden del día
 * @property-read \Illuminate\Database\Eloquent\Collection<Timer> $timers Cronómetros de esta reunión
 */
class Reunion extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'reuniones';

    protected $fillable = [
        'tipo',
        'fecha',
        'hora',
        'modalidad',
        'estado',
        'inicio_at',
        'cierre_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'inicio_at' => 'datetime',
        'cierre_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Preguntas/votaciones de esta reunión.
     * 
     * @return HasMany
     */
    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'reunion_id');
    }

    /**
     * Puntos del orden del día de esta reunión.
     * 
     * @return HasMany
     */
    public function ordenDia(): HasMany
    {
        return $this->hasMany(OrdenDia::class, 'reunion_id');
    }

    /**
     * Cronómetros de esta reunión.
     * 
     * @return HasMany
     */
    public function timers(): HasMany
    {
        return $this->hasMany(Timer::class, 'reunion_id');
    }

    /**
     * Verificar si la reunión está en curso.
     * 
     * @return bool
     */
    public function estaEnCurso(): bool
    {
        return $this->estado === 'en_curso';
    }

    /**
     * Verificar si la reunión está finalizada.
     * 
     * @return bool
     */
    public function estaFinalizada(): bool
    {
        return $this->estado === 'finalizada';
    }

    /**
     * Iniciar la reunión.
     * 
     * @return bool
     */
    public function iniciar(): bool
    {
        return $this->update([
            'estado' => 'en_curso',
            'inicio_at' => now(),
        ]);
    }

    /**
     * Cerrar la reunión.
     * 
     * @return bool
     */
    public function cerrar(): bool
    {
        return $this->update([
            'estado' => 'finalizada',
            'cierre_at' => now(),
        ]);
    }
}
