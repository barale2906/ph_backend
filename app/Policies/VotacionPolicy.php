<?php

namespace App\Policies;

use App\Models\User;

class VotacionPolicy
{
    /**
     * Solo ADMIN_PH puede iniciar/cerrar asamblea
     */
    public function iniciarAsamblea(User $user): bool
    {
        return $user->rol === 'ADMIN_PH';
    }

    /**
     * Solo ADMIN_PH puede cerrar asamblea
     */
    public function cerrarAsamblea(User $user): bool
    {
        return $user->rol === 'ADMIN_PH';
    }

    /**
     * Solo ADMIN_PH puede iniciar/cerrar votaciones
     */
    public function gestionarVotaciones(User $user): bool
    {
        return $user->rol === 'ADMIN_PH';
    }

    /**
     * Nadie puede borrar votos (inmutables)
     */
    public function delete(User $user): bool
    {
        return false;
    }

    /**
     * Nadie puede actualizar votos (inmutables)
     */
    public function update(User $user): bool
    {
        return false;
    }
}

