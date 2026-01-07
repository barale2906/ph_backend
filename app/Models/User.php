<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * PHs asociados a este usuario
     */
    public function phs(): BelongsToMany
    {
        return $this->belongsToMany(Ph::class, 'usuario_ph')
            ->withPivot('rol')
            ->withTimestamps();
    }

    /**
     * Verificar si el usuario tiene acceso a un PH específico
     */
    public function tieneAccesoPh(int $phId): bool
    {
        if ($this->rol === 'SUPER_ADMIN') {
            return true;
        }

        return $this->phs()->where('phs.id', $phId)->exists();
    }

    /**
     * Obtener el rol del usuario para un PH específico
     */
    public function rolEnPh(int $phId): ?string
    {
        $ph = $this->phs()->where('phs.id', $phId)->first();
        return $ph?->pivot->rol ?? $this->rol;
    }

    /**
     * Verificar si el usuario es super admin
     */
    public function esSuperAdmin(): bool
    {
        return $this->rol === 'SUPER_ADMIN';
    }

    /**
     * Verificar si el usuario es admin de PH
     */
    public function esAdminPh(): bool
    {
        return $this->rol === 'ADMIN_PH';
    }

    /**
     * Verificar si el usuario es logística
     */
    public function esLogistica(): bool
    {
        return $this->rol === 'LOGISTICA';
    }
}
