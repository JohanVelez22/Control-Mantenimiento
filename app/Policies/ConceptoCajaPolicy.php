<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ConceptoCaja;

class ConceptoCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function update(User $user, ConceptoCaja $concepto): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function delete(User $user, ConceptoCaja $concepto): bool
    {
        return $user->isAdmin() && !$concepto->movimientos()->exists();
    }
}