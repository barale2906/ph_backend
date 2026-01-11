<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->esSuperAdmin() || $user->rol === 'ADMIN_PH';
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Super admin puede ver todos
        if ($user->esSuperAdmin()) {
            return true;
        }

        // Usuario puede ver su propia información
        if ($user->id === $model->id) {
            return true;
        }

        // Admin PH puede ver usuarios de sus PHs
        if ($user->rol === 'ADMIN_PH') {
            // Verificar si el usuario a ver tiene acceso a alguna PH del admin
            $userPhs = $user->phs()->pluck('phs.id')->toArray();
            $modelPhs = $model->phs()->pluck('phs.id')->toArray();
            
            return !empty(array_intersect($userPhs, $modelPhs));
        }

        return false;
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->esSuperAdmin();
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admin puede actualizar todos
        if ($user->esSuperAdmin()) {
            return true;
        }

        // Usuario puede actualizar su propia información (excepto rol)
        if ($user->id === $model->id) {
            return true;
        }

        // Admin PH puede actualizar usuarios de sus PHs (pero no cambiar rol a SUPER_ADMIN)
        if ($user->rol === 'ADMIN_PH') {
            $userPhs = $user->phs()->pluck('phs.id')->toArray();
            $modelPhs = $model->phs()->pluck('phs.id')->toArray();
            
            return !empty(array_intersect($userPhs, $modelPhs));
        }

        return false;
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Solo super admin puede eliminar usuarios
        // Y no se puede auto-eliminar
        return $user->esSuperAdmin() && $user->id !== $model->id;
    }
}
