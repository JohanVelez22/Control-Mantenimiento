<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Traits\HandlesAbono;

class AbonoController extends Controller
{
    use HandlesAbono;

    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        return $this->storeAbono(
            $mantenimiento,
            $request,
            'Abono Mantenimiento',
            'Abono de $' . number_format($request->monto, 0, ',', '.') . ' registrado y añadido a caja correctamente.'
        );
    }

    public function destroy(\App\Models\Abono $abono)
    {
        return $this->destroyAbono($abono, 'Abono eliminado correctamente y su ingreso removido de la caja.');
    }
}