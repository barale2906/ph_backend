<?php

namespace App\Models\Inmuebles;

use App\Models\Asistentes\Asistente;
use App\Models\Asistentes\AsistenteInmueble;
use App\Models\Concerns\UsesPhDatabase;
use App\Models\Votaciones\Voto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de Inmueble.
 * 
 * Representa un apartamento, local, parqueadero u otro espacio
 * dentro de una Propiedad Horizontal.
 * 
 * @property int $id Identificador único del inmueble
 * @property string $nomenclatura Código único del inmueble (ej: APT-101, LOC-01)
 * @property float $coeficiente Coeficiente de copropiedad expresado como porcentaje
 * @property string $tipo Tipo de inmueble (apartamento, local, parqueadero, etc.)
 * @property string|null $propietario_documento Número de documento del propietario
 * @property string|null $propietario_nombre Nombre del propietario
 * @property string|null $telefono Teléfono de contacto
 * @property string|null $email Correo electrónico de contacto
 * @property bool $activo Indica si el inmueble está activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Asistente> $asistentes Asistentes que representan este inmueble
 * @property-read \Illuminate\Database\Eloquent\Collection<Voto> $votos Votos emitidos por este inmueble
 */
class Inmueble extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'inmuebles';

    protected $fillable = [
        'nomenclatura',
        'coeficiente',
        'tipo',
        'propietario_documento',
        'propietario_nombre',
        'telefono',
        'email',
        'activo',
    ];

    protected $casts = [
        'coeficiente' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Asistentes que representan este inmueble.
     * 
     * @return BelongsToMany
     */
    public function asistentes(): BelongsToMany
    {
        return $this->belongsToMany(Asistente::class, 'asistente_inmueble')
            ->using(AsistenteInmueble::class)
            ->withPivot('coeficiente', 'poder_url')
            ->withTimestamps();
    }

    /**
     * Votos emitidos por este inmueble.
     * 
     * @return HasMany
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'inmueble_id');
    }

    /**
     * Verificar si el inmueble está activo.
     * 
     * @return bool
     */
    public function estaActivo(): bool
    {
        return $this->activo === true;
    }
}
