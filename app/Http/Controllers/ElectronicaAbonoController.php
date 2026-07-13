<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Traits\HandlesAbono;

class ElectronicaAbonoController extends Controller
{
    use HandlesAbono;

    public function store(Request $request, Electronica $electronica)
    {
        return $this->storeAbono(
            $electronica,
            $request,
            'Abono Electrónica',
            'Abono de $' . number_format($request->monto, 0, ',', '.') . ' registrado y añadido a caja correctamente.'
        );
    }

    public function destroy(\App\Models\Abono $abono)
    {
        return $this->destroyAbono($abono, 'Abono eliminado correctamente y su ingreso removido de la caja.');
    }
}