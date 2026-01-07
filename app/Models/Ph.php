<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ph extends Model
{
    use HasFactory;

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

