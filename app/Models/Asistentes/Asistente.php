<?php

namespace App\Models\Asistentes;

use App\Events\AsistenteEliminado;
use App\Events\AsistenteRegistrado;
use App\Models\Concerns\UsesPhDatabase;
use App\Models\Inmuebles\Inmueble;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Modelo de Asistente.
 * 
 * Representa una persona que participa en las reuniones de la PH.
 * Un asistente puede representar varios inmuebles.
 * 
 * @property int $id Identificador único del asistente
 * @property string $nombre Nombre completo del asistente
 * @property string|null $documento Número de documento (puede ser nulo)
 * @property string|null $telefono Número de teléfono
 * @property string|null $codigo_acceso Código único de acceso (generado automáticamente)
 * @property int|null $barcode_numero Número de código de barras asignado para papeleta física
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<Inmueble> $inmuebles Inmuebles que representa este asistente
 */
class Asistente extends Model
{
    use UsesPhDatabase, HasFactory;

    protected $table = 'asistentes';

    protected $fillable = [
        'nombre',
        'documento',
        'telefono',
        'codigo_acceso',
        'barcode_numero',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot del modelo.
     * 
     * Genera automáticamente el código de acceso si no se proporciona.
     * Dispara eventos cuando se crea o elimina un asistente.
     * 
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($asistente) {
            if (empty($asistente->codigo_acceso)) {
                $asistente->codigo_acceso = static::generarCodigoAcceso();
            }
        });

        static::created(function ($asistente) {
            // Disparar evento cuando se registra un asistente
            event(new AsistenteRegistrado($asistente));
        });

        static::deleting(function ($asistente) {
            // Guardar IDs de inmuebles antes de eliminar para el evento
            // Usar una propiedad temporal que se pasará al evento
            $asistente->setAttribute('_inmuebles_ids', $asistente->inmuebles()->pluck('inmuebles.id')->toArray());
        });

        static::deleted(function ($asistente) {
            // Disparar evento cuando se elimina un asistente
            // El asistente ya fue eliminado, pero podemos pasar los IDs guardados
            $inmueblesIds = $asistente->getAttribute('_inmuebles_ids') ?? [];
            event(new AsistenteEliminado($asistente, $inmueblesIds));
        });
    }

    /**
     * Inmuebles que representa este asistente.
     * 
     * @return BelongsToMany
     */
    public function inmuebles(): BelongsToMany
    {
        return $this->belongsToMany(Inmueble::class, 'asistente_inmueble')
            ->using(AsistenteInmueble::class)
            ->withPivot('coeficiente', 'poder_url')
            ->withTimestamps();
    }

    /**
     * Genera un código de acceso único.
     * 
     * @return string
     */
    protected static function generarCodigoAcceso(): string
    {
        do {
            $codigo = strtoupper(Str::random(8));
        } while (static::where('codigo_acceso', $codigo)->exists());

        return $codigo;
    }

    /**
     * Verificar si el asistente tiene documento registrado.
     * 
     * @return bool
     */
    public function tieneDocumento(): bool
    {
        return !empty($this->documento);
    }
}
