<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Factura;

class FacturaPolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function view(User $user, Factura $factura): bool
    {
        return !$user->isInvitado();
    }

    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function update(User $user, Factura $factura): bool
    {
        return !$user->isInvitado() && $factura->estado !== 'anulada';
    }

    public function delete(User $user, Factura $factura): bool
    {
        return $user->isAdmin();
    }

    public function anular(User $user, Factura $factura): bool
    {
        return !$user->isInvitado() && $factura->estado !== 'anulada';
    }

    public function reactivar(User $user, Factura $factura): bool
    {
        return !$user->isInvitado() && $factura->estado === 'anulada';
    }

    public function imprimir(User $user, Factura $factura): bool
    {
        return !$user->isInvitado();
    }
}