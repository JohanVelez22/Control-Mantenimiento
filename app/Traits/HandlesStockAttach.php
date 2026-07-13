<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\StockService;

trait HandlesStockAttach
{
    /**
     * Añadir stock a un modelo (Mantenimiento o Electronica)
     */
    protected function attachStock($model, array $validated, string $successMsg): \Illuminate\Http\RedirectResponse
    {
        if ($model->anulado) {
            return redirect()->back()->with('error', 'No se pueden añadir repuestos a un registro anulado.');
        }

        try {
            DB::transaction(function () use ($model, $validated) {
                $stock = \App\Models\Stock::findOrFail($validated['stock_id']);

                // Salida atómica del stock (bloquea la fila y evita saldo negativo).
                $stock = app(StockService::class)->salida($stock, $validated['cantidad']);

                // Agregar al modelo (suma si ya existía, sino attach).
                $existing = $model->stocks()->where('stock_id', $stock->id)->first();
                if ($existing) {
                    $newCantidad = $existing->pivot->cantidad + $validated['cantidad'];
                    $model->stocks()->updateExistingPivot($stock->id, ['cantidad' => $newCantidad]);
                } else {
                    $model->stocks()->attach($stock->id, [
                        'cantidad'        => $validated['cantidad'],
                        'precio_unitario' => $stock->precio_venta,
                    ]);
                }

                // Sumar al costo del modelo
                $model->increment('costo', $stock->precio_venta * $validated['cantidad']);
            });

            return redirect()->back()->with('success', $successMsg);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Error agregando repuesto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al agregar el repuesto. Intente nuevamente.')->withInput();
        }
    }

    /**
     * Quitar stock de un modelo (Mantenimiento o Electronica)
     */
    protected function detachStock($model, int $stock_id, string $successMsg): \Illuminate\Http\RedirectResponse
    {
        if ($model->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un registro anulado.');
        }

        $repuesto = $model->stocks()->where('stock_id', $stock_id)->first();

        if (!$repuesto) {
            return redirect()->back()->with('error', 'El repuesto no existe en este registro.');
        }

        try {
            DB::transaction(function () use ($model, $repuesto, $stock_id) {
                $cantidad = $repuesto->pivot->cantidad;
                $precio   = $repuesto->pivot->precio_unitario;

                // Devolver al stock de forma atómica
                app(StockService::class)->entrada($stock_id, $cantidad);

                // Restar costo del modelo
                $model->decrement('costo', $precio * $cantidad);

                // Desvincular
                $model->stocks()->detach($stock_id);
            });

            return redirect()->back()->with('success', $successMsg);
        } catch (\Exception $e) {
            Log::error('Error eliminando repuesto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el repuesto. Intente nuevamente.');
        }
    }
}