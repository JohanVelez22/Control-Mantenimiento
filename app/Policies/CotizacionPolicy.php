<?php

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\User;

class CotizacionPolicy
{
    /**
     * Determina si el usuario puede listar cotizaciones.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver una cotización.
     */
    public function view(User $user, Cotizacion $cotizacion): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede crear cotizaciones.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede actualizar una cotización.
     */
    public function update(User $user, Cotizacion $cotizacion): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede convertir una cotización a factura o mantenimiento.
     */
    public function convertir(User $user, Cotizacion $cotizacion): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar cotizaciones.
     */
    public function delete(User $user, Cotizacion $cotizacion): bool
    {
        return $user->isAdmin();
    }
}
