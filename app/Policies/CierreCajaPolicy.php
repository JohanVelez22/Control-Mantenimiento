<?php

namespace App\Policies;

use App\Models\CierreCaja;
use App\Models\User;

class CierreCajaPolicy
{
    /**
     * Determina si el usuario puede listar cierres de caja.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver el detalle de un cierre de caja.
     */
    public function view(User $user, CierreCaja $cierre): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede registrar o cerrar caja diaria.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }
}
