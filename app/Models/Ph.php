<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;

/**
 * Modelo de Propiedad Horizontal (PH).
 * 
 * Este modelo pertenece a la base de datos MASTER y NO debe usar el trait UsesPhDatabase.
 * Representa la información general de cada Propiedad Horizontal en el sistema.
 */
class Ph extends Model
{
    use HasFactory;

    /**
     * Obtiene la conexión de base de datos para el modelo.
     * 
     * Este modelo SIEMPRE debe usar la conexión master (por defecto),
     * ya que pertenece a la base de datos central del sistema.
     * 
     * @return string Nombre de la conexión
     */
    public function getConnectionName(): string
    {
        return Config::get('database.default');
    }

    protected $fillable = [
        'nit',
        'nombre',
        'db_name',
        'estado',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Usuarios asociados a este PH
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuario_ph')
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * Eventos de auditoría de este PH
     */
    public function eventosAuditoria(): HasMany
    {
        return $this->hasMany(AuditoriaEvento::class, 'ph_id');
    }

    /**
     * Verificar si el PH está activo
     */
    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }
}

