<?php

namespace App\Models\Reuniones;

use App\Models\Concerns\UsesPhDatabase;
use App\Models\Votaciones\Pregunta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de Orden del Día.
 * 
 * Representa un punto del orden del día de una reunión.
 * Puede estar relacionado con una pregunta/votación.
 * 
 * @property int $id Identificador único del punto
 * @property int $reunion_id ID de la reunión
 * @property int $orden Orden de presentación
 * @property string $titulo Título del punto
 * @property string|null $descripcion Descripción detallada
 * @property string|null $tipo Tipo de punto (información, votación, etc.)
 * @property int|null $pregunta_id ID de la pregunta asociada (si aplica)
 * @property bool $tratado Indica si ya fue tratado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Reunion $reunion Reunión a la que pertenece
 * @property-read Pregunta|null $pregunta Pregunta asociada (si aplica)
 */
class OrdenDia extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'orden_dia';

    protected $fillable = [
        'reunion_id',
        'orden',
        'titulo',
        'descripcion',
        'tipo',
        'pregunta_id',
        'tratado',
    ];

    protected $casts = [
        'tratado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Reunión a la que pertenece este punto.
     * 
     * @return BelongsTo
     */
    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class, 'reunion_id');
    }

    /**
     * Pregunta asociada a este punto (si aplica).
     * 
     * @return BelongsTo
     */
    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Votaciones\Pregunta::class, 'pregunta_id');
    }

    /**
     * Marcar el punto como tratado.
     * 
     * @return bool
     */
    public function marcarComoTratado(): bool
    {
        return $this->update(['tratado' => true]);
    }
}
