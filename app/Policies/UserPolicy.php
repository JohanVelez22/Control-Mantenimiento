<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Solo administradores pueden listar usuarios del sistema.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Un usuario puede ver su propio perfil o el admin puede ver cualquiera.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Solo administradores pueden crear nuevos usuarios.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Un usuario puede actualizar sus propios datos o el admin puede actualizar cualquiera.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Solo administradores pueden eliminar usuarios, y nunca a sí mismos.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}

