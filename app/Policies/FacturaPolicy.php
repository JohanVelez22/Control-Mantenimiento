<?php

namespace App\Policies;

use App\Models\Factura;
use App\Models\User;

class FacturaPolicy
{
    /**
     * Determina si el usuario puede listar facturas.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver una factura específica.
     */
    public function view(User $user, Factura $factura): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede crear facturas.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede actualizar una factura.
     */
    public function update(User $user, Factura $factura): bool
    {
        return ! $user->isInvitado();
    }
}
