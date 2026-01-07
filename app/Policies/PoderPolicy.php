<?php

namespace App\Policies;

use App\Models\User;

class PoderPolicy
{
    /**
     * Solo LOGISTICA puede registrar poderes
     */
    public function registrar(User $user): bool
    {
        return $user->rol === 'LOGISTICA';
    }

    /**
     * Solo LOGISTICA y ADMIN_PH pueden ver poderes
     */
    public function view(User $user): bool
    {
        return in_array($user->rol, ['LOGISTICA', 'ADMIN_PH']);
    }
}

