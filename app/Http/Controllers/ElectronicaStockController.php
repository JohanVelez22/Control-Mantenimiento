<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        try {
            DB::transaction(function () use ($electronica, $validated) {
                $stock = Stock::findOrFail($validated['stock_id']);

                // Salida atómica del stock (bloquea la fila y evita saldo negativo).
                $stock = app(StockService::class)->salida($stock, $validated['cantidad']);

                // Agregar a la electrónica
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

                // Sumar al costo de la electrónica
                $electronica->increment('costo', $stock->precio_venta * $validated['cantidad']);
            });

            return redirect()->back()->with('success', 'Repuesto agregado a la electrónica y descontado del stock.');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Error agregando repuesto a electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al agregar el repuesto. Intente nuevamente.')->withInput();
        }
    }

    public function destroy(Electronica $electronica, $stock_id)
    {
        if ($electronica->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un registro anulado.');
        }

        $repuesto = $electronica->stocks()->where('stock_id', $stock_id)->first();

        if (!$repuesto) {
            return redirect()->back()->with('error', 'El repuesto no existe en este registro.');
        }

        try {
            DB::transaction(function () use ($electronica, $repuesto, $stock_id) {
                $cantidad = $repuesto->pivot->cantidad;
                $precio = $repuesto->pivot->precio_unitario;

                // Devolver al stock de forma atómica
                app(StockService::class)->entrada((int) $stock_id, $cantidad);

                // Restar costo
                $electronica->decrement('costo', $precio * $cantidad);

                // Desvincular
                $electronica->stocks()->detach($stock_id);
            });

            return redirect()->back()->with('success', 'Repuesto eliminado y devuelto al stock.');
        } catch (\Exception $e) {
            Log::error('Error eliminando repuesto de electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el repuesto. Intente nuevamente.');
        }
    }
}
