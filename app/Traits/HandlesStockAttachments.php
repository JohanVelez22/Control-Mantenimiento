<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\StockService;
use App\Models\Stock;

trait HandlesStockAttachments
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $parent (Mantenimiento|Electronica)
     * @param array $validated ['stock_id', 'cantidad']
     * @param string $successMessage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attachStock($parent, array $validated, string $successMessage)
    {
        if ($parent->anulado) {
            return redirect()->back()->with('error', 'No se pueden añadir repuestos a un registro anulado.');
        }

        try {
            DB::transaction(function () use ($parent, $validated) {
                $stock = Stock::findOrFail($validated['stock_id']);

                // Salida atómica del stock (bloquea la fila y evita saldo negativo)
                $stock = app(StockService::class)->salida($stock, $validated['cantidad']);

                // Agregar al padre (suma si ya existía, sino attach)
                $existing = $parent->stocks()->where('stock_id', $stock->id)->first();
                if ($existing) {
                    $newCantidad = $existing->pivot->cantidad + $validated['cantidad'];
                    $parent->stocks()->updateExistingPivot($stock->id, ['cantidad' => $newCantidad]);
                } else {
                    $parent->stocks()->attach($stock->id, [
                        'cantidad' => $validated['cantidad'],
                        'precio_unitario' => $stock->precio_venta,
                    ]);
                }

                // Sumar al costo del padre
                $parent->increment('costo', $stock->precio_venta * $validated['cantidad']);
            });

            return redirect()->back()->with('success', $successMessage);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Error agregando repuesto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al agregar el repuesto. Intente nuevamente.')->withInput();
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $parent (Mantenimiento|Electronica)
     * @param int $stock_id
     * @param string $successMessage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function detachStock($parent, int $stock_id, string $successMessage)
    {
        if ($parent->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un registro anulado.');
        }

        $repuesto = $parent->stocks()->where('stock_id', $stock_id)->first();

        if (!$repuesto) {
            return redirect()->back()->with('error', 'El repuesto no existe en este registro.');
        }

        try {
            DB::transaction(function () use ($parent, $repuesto, $stock_id) {
                $cantidad = $repuesto->pivot->cantidad;
                $precio = $repuesto->pivot->precio_unitario;

                // Devolver al stock de forma atómica
                app(StockService::class)->entrada((int) $stock_id, $cantidad);

                // Restar costo del padre
                $parent->decrement('costo', $precio * $cantidad);

                // Desvincular
                $parent->stocks()->detach($stock_id);
            });

            return redirect()->back()->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Error eliminando repuesto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el repuesto. Intente nuevamente.');
        }
    }
}