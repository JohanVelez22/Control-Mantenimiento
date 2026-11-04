<?php

namespace App\Policies;

use App\Models\MovimientoCaja;
use App\Models\User;

class MovimientoCajaPolicy
{
    /**
     * Determina si el usuario puede listar movimientos de caja.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver un movimiento específico.
     */
    public function view(User $user, MovimientoCaja $movimiento): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede registrar movimientos de caja.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede editar un movimiento de caja abierto.
     */
    public function update(User $user, MovimientoCaja $movimiento): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo administradores pueden anular movimientos contables.
     */
    public function anular(User $user, MovimientoCaja $movimiento): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo administradores pueden eliminar movimientos de caja.
     */
    public function delete(User $user, MovimientoCaja $movimiento): bool
    {
        return $user->isAdmin();
    }
}

