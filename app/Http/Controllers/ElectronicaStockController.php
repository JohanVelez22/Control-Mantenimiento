<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Models\Stock;

class ElectronicaStockController extends Controller
{
    public function store(Request $request, Electronica $electronica)
    {
        if ($electronica->anulado) {
            return redirect()->back()->with('error', 'No se pueden añadir repuestos a un registro anulado.');
        }

        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);

        if ($stock->cantidad < $validated['cantidad']) {
            return redirect()->back()->with('error', 'Stock insuficiente para el repuesto seleccionado.');
        }

        // Descontar del stock
        $stock->decrement('cantidad', $validated['cantidad']);

        // Agregar al electronica
        $existing = $electronica->stocks()->where('stock_id', $stock->id)->first();
        if ($existing) {
            $newCantidad = $existing->pivot->cantidad + $validated['cantidad'];
            $electronica->stocks()->updateExistingPivot($stock->id, ['cantidad' => $newCantidad]);
        } else {
            $electronica->stocks()->attach($stock->id, [
                'cantidad' => $validated['cantidad'],
                'precio_unitario' => $stock->precio_venta,
            ]);
        }

        // Sumar al costo de la electronica
        $electronica->increment('costo', $stock->precio_venta * $validated['cantidad']);

        return redirect()->back()->with('success', 'Repuesto agregado a la electrónica y descontado del stock.');
    }

    public function destroy(Electronica $electronica, $stock_id)
    {
        if ($electronica->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un registro anulado.');
        }

        $repuesto = $electronica->stocks()->where('stock_id', $stock_id)->first();

        if ($repuesto) {
            $cantidad = $repuesto->pivot->cantidad;
            $precio = $repuesto->pivot->precio_unitario;

            // Devolver al stock
            Stock::where('id', $stock_id)->increment('cantidad', $cantidad);

            // Restar costo
            $electronica->decrement('costo', $precio * $cantidad);

            // Desvincular
            $electronica->stocks()->detach($stock_id);

            return redirect()->back()->with('success', 'Repuesto eliminado y devuelto al stock.');
        }

        return redirect()->back()->with('error', 'El repuesto no existe en este registro.');
    }
}
