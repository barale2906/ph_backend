<?php

namespace App\Models\Estamentos;

use App\Models\Concerns\UsesPhDatabase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Estamento.
 * 
 * Representa un tipo o categoría de asistentes en la PH.
 * Ejemplos: Propietario, Arrendatario, Administrador, Representante Legal.
 * 
 * @property int $id Identificador único del estamento
 * @property string $nombre Nombre único del estamento
 * @property string|null $descripcion Descripción detallada
 * @property bool $activo Indica si está activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Estamento extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'estamentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Verificar si el estamento está activo.
     * 
     * @return bool
     */
    public function estaActivo(): bool
    {
        return $this->activo === true;
    }
}
