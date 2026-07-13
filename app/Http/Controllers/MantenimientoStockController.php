<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Traits\HandlesStockAttach;

class MantenimientoStockController extends Controller
{
    use HandlesStockAttach;

    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'stock_id'  => 'required|exists:stocks,id',
            'cantidad'  => 'required|integer|min:1',
        ]);

        return $this->attachStock(
            $mantenimiento,
            $validated,
            'Repuesto agregado al mantenimiento y descontado del stock.'
        );
    }

    public function destroy(Mantenimiento $mantenimiento, int $stock_id)
    {
        return $this->detachStock(
            $mantenimiento,
            $stock_id,
            'Repuesto eliminado y devuelto al stock.'
        );
    }
}