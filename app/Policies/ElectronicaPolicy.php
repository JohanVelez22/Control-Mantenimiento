<?php

namespace App\Policies;

use App\Models\Electronica;
use App\Models\User;

class ElectronicaPolicy
{
    /**
     * Todos los usuarios autenticados pueden ver electrónica.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Electronica $electronica): bool
    {
        return true;
    }

    /**
     * Solo usuarios no invitados pueden crear registros de electrónica.
     */
    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    /**
     * Solo usuarios no invitados pueden actualizar registros de electrónica.
     */
    public function update(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar.
     */
    public function delete(User $user, Electronica $electronica): bool
    {
        return $user->isAdmin();
    }
}
