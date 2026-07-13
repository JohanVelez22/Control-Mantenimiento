<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MovimientoCaja;

class MovimientoCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MovimientoCaja $movimiento): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function update(User $user, MovimientoCaja $movimiento): bool
    {
        return !$user->isInvitado() && !$movimiento->anulado;
    }

    public function delete(User $user, MovimientoCaja $movimiento): bool
    {
        return $user->isAdmin();
    }

    public function anular(User $user, MovimientoCaja $movimiento): bool
    {
        return !$user->isInvitado();
    }

    public function duplicar(User $user, MovimientoCaja $movimiento): bool
    {
        return !$user->isInvitado();
    }

    public function abonar(User $user, MovimientoCaja $movimiento): bool
    {
        return !$user->isInvitado() && !$movimiento->anulado && $movimiento->saldo_pendiente > 0;
    }

    public function imprimir(User $user, MovimientoCaja $movimiento): bool
    {
        return !$user->isInvitado();
    }

    public function crearConcepto(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function eliminarConcepto(User $user): bool
    {
        return $user->isAdmin();
    }
}