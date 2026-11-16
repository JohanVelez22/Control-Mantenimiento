<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    /**
     * Determina si el usuario puede listar productos de inventario.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver la ficha de un producto.
     */
    public function view(User $user, Stock $stock): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede crear nuevos productos en inventario.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede editar datos de stock/precios.
     */
    public function update(User $user, Stock $stock): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede dar de baja existencias por merma.
     */
    public function darDeBaja(User $user, Stock $stock): bool
    {
        return ! $user->isInvitado();
    }
}
