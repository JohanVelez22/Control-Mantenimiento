<?php

namespace App\Policies;

use App\Models\Electronica;
use App\Models\User;

class ElectronicaPolicy
{
    /**
     * Solo administradores y técnicos pueden listar electrónica completa.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Ver detalle de electrónica.
     */
    public function view(User $user, Electronica $electronica): bool
    {
        if (! $user->isInvitado()) {
            return true;
        }

        // Si el usuario invitado es el cliente dueño del equipo (mismo correo)
        $cliente = \App\Models\Cliente::where('email', $user->email)->first();
        if ($cliente) {
            return $electronica->equipo?->cliente_id === $cliente->id;
        }

        // Si es el invitado genérico, verificar si validó la orden en la sesión actual
        $autorizadas = session('consultas_autorizadas_elec', []);
        return in_array($electronica->id, $autorizadas);
    }

    /**
     * Solo usuarios no invitados pueden crear registros de electrónica.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo usuarios no invitados pueden actualizar registros de electrónica.
     */
    public function update(User $user, Electronica $electronica): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar.
     */
    public function delete(User $user, Electronica $electronica): bool
    {
        return $user->isAdmin();
    }
}
