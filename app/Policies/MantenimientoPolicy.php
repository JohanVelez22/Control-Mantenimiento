<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Mantenimiento;

class MantenimientoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado() && !$mantenimiento->anulado;
    }

    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->isAdmin();
    }

    public function anular(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado();
    }

    public function reactivar(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado();
    }

    public function duplicar(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado();
    }

    public function agregarStock(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado() && !$mantenimiento->anulado;
    }

    public function quitarStock(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado() && !$mantenimiento->anulado;
    }

    public function abonar(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado() && !$mantenimiento->anulado && $mantenimiento->saldo_pendiente > 0;
    }

    public function facturar(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado() && !$mantenimiento->anulado && $mantenimiento->estado === 'terminado' && $mantenimiento->fecha_salida;
    }

    public function imprimir(User $user, Mantenimiento $mantenimiento): bool
    {
        return !$user->isInvitado();
    }

    public function reportes(User $user): bool
    {
        return !$user->isInvitado();
    }
}