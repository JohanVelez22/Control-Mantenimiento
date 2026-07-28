<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;

class MantenimientoPolicy
{
    /**
     * Todos los usuarios autenticados pueden ver mantenimientos.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return true;
    }

    /**
     * Solo usuarios no invitados pueden crear mantenimientos.
     */
    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    /**
     * Solo usuarios no invitados pueden actualizar mantenimientos.
     */
    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar.
     */
    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->isAdmin();
    }
}
