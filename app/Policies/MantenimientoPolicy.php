<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;

class MantenimientoPolicy
{
    /**
     * Solo administradores y técnicos pueden listar mantenimientos completos.
     */
    public function viewAny(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Ver detalle de mantenimiento.
     */
    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        if (! $user->isInvitado()) {
            return true;
        }

        // Si el usuario invitado es el cliente dueño del equipo (mismo correo)
        $cliente = \App\Models\Cliente::where('email', $user->email)->first();
        if ($cliente) {
            return $mantenimiento->equipo?->cliente_id === $cliente->id;
        }

        // Si es el invitado genérico, verificar si validó la orden en la sesión actual
        $autorizadas = session('consultas_autorizadas', []);
        return in_array($mantenimiento->id, $autorizadas);
    }

    /**
     * Solo usuarios no invitados pueden crear mantenimientos.
     */
    public function create(User $user): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo usuarios no invitados pueden actualizar mantenimientos.
     */
    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return ! $user->isInvitado();
    }

    /**
     * Solo administradores pueden anular o eliminar.
     */
    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->isAdmin();
    }
}
