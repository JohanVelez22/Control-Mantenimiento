<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Electronica;
use App\Traits\HandlesAbono;
use Illuminate\Http\Request;

class ElectronicaAbonoController extends Controller
{
    use HandlesAbono;

    public function store(Request $request, Electronica $electronica)
    {
        return $this->storeAbono(
            $electronica,
            $request,
            'Abono Electrónica'
        );
    }

    public function destroy(Abono $abono)
    {
        return $this->destroyAbono($abono, 'electronica');
    }
}
