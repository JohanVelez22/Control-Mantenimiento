<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Traits\HandlesStockAttach;

class ElectronicaStockController extends Controller
{
    use HandlesStockAttach;

    public function store(Request $request, Electronica $electronica)
    {
        $validated = $request->validate([
            'stock_id'  => 'required|exists:stocks,id',
            'cantidad'  => 'required|integer|min:1',
        ]);

        return $this->attachStock(
            $electronica,
            $validated,
            'Repuesto agregado a la electrónica y descontado del stock.'
        );
    }

    public function destroy(Electronica $electronica, int $stock_id)
    {
        return $this->detachStock(
            $electronica,
            $stock_id,
            'Repuesto eliminado y devuelto al stock.'
        );
    }
}