<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaEvento extends Model
{
    use HasFactory;

    protected $table = 'auditoria_eventos';

    protected $fillable = [
        'evento',
        'tipo',
        'ph_id',
        'usuario_id',
        'reunion_id',
        'datos',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * PH relacionado
     */
    public function ph(): BelongsTo
    {
        return $this->belongsTo(Ph::class, 'ph_id');
    }

    /**
     * Usuario relacionado
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registrar un evento de auditoría
     */
    public static function registrar(
        string $evento,
        string $tipo,
        ?int $phId = null,
        ?int $usuarioId = null,
        ?string $reunionId = null,
        ?array $datos = null
    ): self {
        return self::create([
            'evento' => $evento,
            'tipo' => $tipo,
            'ph_id' => $phId,
            'usuario_id' => $usuarioId,
            'reunion_id' => $reunionId,
            'datos' => $datos,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

