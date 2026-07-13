<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CategoriaStock;

class CategoriaStockPolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function update(User $user, CategoriaStock $categoria): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function delete(User $user, CategoriaStock $categoria): bool
    {
        return $user->isAdmin() && !$categoria->stocks()->exists();
    }
}