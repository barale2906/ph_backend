<?php

namespace App\Models\Votaciones;

use App\Models\Concerns\UsesPhDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Opción (Respuesta).
 * 
 * Representa una opción de respuesta para una pregunta/votación.
 * 
 * @property int $id Identificador único de la opción
 * @property int $pregunta_id ID de la pregunta
 * @property string $texto Texto de la opción (ej: "Sí", "No", "Abstención")
 * @property int $orden Orden de visualización
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Pregunta $pregunta Pregunta a la que pertenece
 * @property-read \Illuminate\Database\Eloquent\Collection<Voto> $votos Votos que seleccionaron esta opción
 */
class Opcion extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'opciones';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'orden',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Pregunta a la que pertenece esta opción.
     * 
     * @return BelongsTo
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    /**
     * Votos que seleccionaron esta opción.
     * 
     * @return HasMany
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'opcion_id');
    }
}
