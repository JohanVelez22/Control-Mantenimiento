<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CierreCaja;

class CierreCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return !$user->isInvitado();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function delete(User $user, CierreCaja $cierre): bool
    {
        return $user->isAdmin();
    }
}