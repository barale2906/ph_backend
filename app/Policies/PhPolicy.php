<?php

namespace App\Policies;

use App\Models\Ph;
use App\Models\User;

class PhPolicy
{
    /**
     * Determine if the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LECTURA']);
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, Ph $ph): bool
    {
        if ($user->rol === 'SUPER_ADMIN') {
            return true;
        }

        return $user->tieneAccesoPh($ph->id);
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->rol === 'SUPER_ADMIN';
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, Ph $ph): bool
    {
        if ($user->rol === 'SUPER_ADMIN') {
            return true;
        }

        return $user->rol === 'ADMIN_PH' && $user->tieneAccesoPh($ph->id);
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, Ph $ph): bool
    {
        return $user->rol === 'SUPER_ADMIN';
    }
}

