<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Stock;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Stock $stock): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function update(User $user, Stock $stock): bool
    {
        return !$user->isInvitado();
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->isAdmin();
    }

    public function anular(User $user, Stock $stock): bool
    {
        return !$user->isInvitado();
    }

    public function imprimir(User $user, Stock $stock): bool
    {
        return !$user->isInvitado();
    }

    public function reportes(User $user): bool
    {
        return !$user->isInvitado();
    }
}