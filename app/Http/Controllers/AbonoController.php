<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Mantenimiento;
use App\Traits\HandlesAbono;
use Illuminate\Http\Request;

class AbonoController extends Controller
{
    use HandlesAbono;

    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        return $this->storeAbono(
            $mantenimiento,
            $request,
            'Abono Mantenimiento',
            'Abono de $'.number_format($request->monto, 0, ',', '.').' registrado y añadido a caja correctamente.'
        );
    }

    public function destroy(Abono $abono)
    {
        return $this->destroyAbono($abono, 'Abono eliminado correctamente y su ingreso removido de la caja.');
    }
}
