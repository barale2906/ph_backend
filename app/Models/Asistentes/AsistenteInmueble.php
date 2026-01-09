<?php

namespace App\Models\Asistentes;

use App\Models\Concerns\UsesPhDatabase;
use App\Models\Inmuebles\Inmueble;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Modelo Pivot para la relación Asistente-Inmueble.
 * 
 * Representa la relación entre un asistente y un inmueble que representa.
 * Un asistente puede representar varios inmuebles (mínimo 1).
 * 
 * @property int $id Identificador único de la relación
 * @property int $asistente_id ID del asistente
 * @property int $inmueble_id ID del inmueble
 * @property float $coeficiente Coeficiente que representa este asistente para este inmueble
 * @property string|null $poder_url URL del documento de poder notarial
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read Asistente $asistente Asistente relacionado
 * @property-read Inmueble $inmueble Inmueble relacionado
 */
class AsistenteInmueble extends Pivot
{
    use UsesPhDatabase;

    protected $table = 'asistente_inmueble';

    protected $fillable = [
        'asistente_id',
        'inmueble_id',
        'coeficiente',
        'poder_url',
    ];

    protected $casts = [
        'coeficiente' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Asistente relacionado.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asistente()
    {
        return $this->belongsTo(Asistente::class, 'asistente_id');
    }

    /**
     * Inmueble relacionado.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'inmueble_id');
    }
}
