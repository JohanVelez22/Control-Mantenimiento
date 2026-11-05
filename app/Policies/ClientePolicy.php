<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    /**
     * Determina si el usuario puede ver la lista de clientes.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede ver la ficha de un cliente.
     */
    public function view(User $user, Cliente $cliente): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede registrar clientes.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Determina si el usuario puede editar datos de un cliente.
     */
    public function update(User $user, Cliente $cliente): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar clientes.
     */
    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }
}
