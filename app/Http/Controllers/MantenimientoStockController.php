<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Models\Stock;

class MantenimientoStockController extends Controller
{
    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        if ($mantenimiento->anulado) {
            return redirect()->back()->with('error', 'No se pueden añadir repuestos a un mantenimiento anulado.');
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

        // Agregar al mantenimiento
        // Si el repuesto ya fue agregado, podríamos sumar la cantidad, pero por simplicidad haremos attach
        // o mejor syncWithoutDetaching incrementando
        $existing = $mantenimiento->stocks()->where('stock_id', $stock->id)->first();
        if ($existing) {
            $newCantidad = $existing->pivot->cantidad + $validated['cantidad'];
            $mantenimiento->stocks()->updateExistingPivot($stock->id, ['cantidad' => $newCantidad]);
        } else {
            $mantenimiento->stocks()->attach($stock->id, [
                'cantidad' => $validated['cantidad'],
                'precio_unitario' => $stock->precio_venta,
            ]);
        }

        // Sumar al costo del mantenimiento
        $mantenimiento->increment('costo', $stock->precio_venta * $validated['cantidad']);

        return redirect()->back()->with('success', 'Repuesto agregado al mantenimiento y descontado del stock.');
    }

    public function destroy(Mantenimiento $mantenimiento, $stock_id)
    {
        if ($mantenimiento->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un mantenimiento anulado.');
        }

        $repuesto = $mantenimiento->stocks()->where('stock_id', $stock_id)->first();

        if ($repuesto) {
            $cantidad = $repuesto->pivot->cantidad;
            $precio = $repuesto->pivot->precio_unitario;

            // Devolver al stock
            Stock::where('id', $stock_id)->increment('cantidad', $cantidad);

            // Restar costo del mantenimiento
            $mantenimiento->decrement('costo', $precio * $cantidad);

            // Desvincular
            $mantenimiento->stocks()->detach($stock_id);

            return redirect()->back()->with('success', 'Repuesto eliminado y devuelto al stock.');
        }

        return redirect()->back()->with('error', 'El repuesto no existe en este mantenimiento.');
    }
}
