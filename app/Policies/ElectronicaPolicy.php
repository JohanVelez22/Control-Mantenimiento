<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Electronica;

class ElectronicaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Electronica $electronica): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function update(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado() && !$electronica->anulado;
    }

    public function delete(User $user, Electronica $electronica): bool
    {
        return $user->isAdmin();
    }

    public function anular(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado();
    }

    public function reactivar(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado();
    }

    public function agregarStock(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado() && !$electronica->anulado;
    }

    public function quitarStock(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado() && !$electronica->anulado;
    }

    public function abonar(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado() && !$electronica->anulado && $electronica->saldo_pendiente > 0;
    }

    public function facturar(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado() && !$electronica->anulado && $electronica->estado === 'terminado' && $electronica->fecha_salida;
    }

    public function imprimir(User $user, Electronica $electronica): bool
    {
        return !$user->isInvitado();
    }

    public function reportes(User $user): bool
    {
        return !$user->isInvitado();
    }
}