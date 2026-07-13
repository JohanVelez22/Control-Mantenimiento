<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        try {
            DB::transaction(function () use ($mantenimiento, $validated) {
                $stock = Stock::findOrFail($validated['stock_id']);

                // Salida atómica del stock (bloquea la fila y evita saldo negativo
                // bajo concurrencia). Lanza DomainException si no alcanza.
                $stock = app(StockService::class)->salida($stock, $validated['cantidad']);

                // Agregar al mantenimiento (suma si ya existía, sino attach).
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
            });

            return redirect()->back()->with('success', 'Repuesto agregado al mantenimiento y descontado del stock.');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Error agregando repuesto a mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al agregar el repuesto. Intente nuevamente.')->withInput();
        }
    }

    public function destroy(Mantenimiento $mantenimiento, $stock_id)
    {
        if ($mantenimiento->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un mantenimiento anulado.');
        }

        $repuesto = $mantenimiento->stocks()->where('stock_id', $stock_id)->first();

        if (!$repuesto) {
            return redirect()->back()->with('error', 'El repuesto no existe en este mantenimiento.');
        }

        try {
            DB::transaction(function () use ($mantenimiento, $repuesto, $stock_id) {
                $cantidad = $repuesto->pivot->cantidad;
                $precio = $repuesto->pivot->precio_unitario;

                // Devolver al stock de forma atómica
                app(StockService::class)->entrada((int) $stock_id, $cantidad);

                // Restar costo del mantenimiento
                $mantenimiento->decrement('costo', $precio * $cantidad);

                // Desvincular
                $mantenimiento->stocks()->detach($stock_id);
            });

            return redirect()->back()->with('success', 'Repuesto eliminado y devuelto al stock.');
        } catch (\Exception $e) {
            Log::error('Error eliminando repuesto de mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el repuesto. Intente nuevamente.');
        }
    }
}
