<?php

namespace App\Traits;

use App\Models\Evento;
use App\Models\Stock;
use App\Services\AnulacionService;
use App\Services\OrdenEstadoService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesStockAttach
{
    /**
     * Añadir stock a un modelo (Mantenimiento o Electronica)
     */
    protected function attachStock($model, array $validated, string $successMsg): RedirectResponse
    {
        if ($model->anulado) {
            return redirect()->back()->with('error', 'No se pueden añadir repuestos a un registro anulado.');
        }
        if ($bloqueo = OrdenEstadoService::bloqueoRepuestos($model)) {
            return redirect()->back()->with('error', $bloqueo);
        }

        try {
            DB::transaction(function () use ($model, $validated, &$successMsg) {
                // Bloquear el modelo (Mantenimiento o Electronica) para serializar modificaciones concurrentes
                $lockedModel = get_class($model)::where('id', $model->id)->lockForUpdate()->firstOrFail();
                // B-03: releer tras el bloqueo; otra petición pudo terminar la orden.
                if ($bloqueo = OrdenEstadoService::bloqueoRepuestos($lockedModel)) {
                    throw new \DomainException($bloqueo);
                }

                $stock = Stock::activos()->find($validated['stock_id']);
                if (! $stock) {
                    throw new \DomainException('El repuesto seleccionado no existe o está anulado.');
                }

                // Salida atómica del stock (bloquea la fila y evita saldo negativo).
                $stock = app(StockService::class)->salida($stock, $validated['cantidad']);

                // Determinar precio según tipo de cliente (técnico o normal)
                $cliente = $lockedModel->equipo?->cliente;
                $esTecnico = $cliente && $cliente->tipo_cliente === 'tecnico';
                $precioUnitario = ($esTecnico && $stock->precio_tecnico > 0)
                    ? (float) $stock->precio_tecnico
                    : (float) $stock->precio_venta;

                // Agregar al modelo (suma si ya existía, sino attach).
                $existing = $lockedModel->stocks()->where('stock_id', $stock->id)->first();
                if ($existing) {
                    $newCantidad = $existing->pivot->cantidad + $validated['cantidad'];
                    $totalPrevio = self::totalLinea($existing->pivot->cantidad, $existing->pivot->precio_unitario);
                    $totalNuevo = (float) $validated['cantidad'] * $precioUnitario;
                    $nuevoPrecioUnitario = round(($totalPrevio + $totalNuevo) / $newCantidad, 2);
                    $lockedModel->stocks()->updateExistingPivot($stock->id, [
                        'cantidad' => $newCantidad,
                        'precio_unitario' => $nuevoPrecioUnitario,
                    ]);
                    // M-18: el costo sube exactamente lo que sube la línea (cantidad × precio
                    // promedio redondeado); así quitar el repuesto descuenta lo mismo que se sumó.
                    $aumento = round(self::totalLinea($newCantidad, $nuevoPrecioUnitario) - $totalPrevio, 2);
                    $successMsg = "Se sumaron {$validated['cantidad']} unidad(es) a '{$stock->producto}' (total actual: {$newCantidad} uds).";
                } else {
                    $lockedModel->stocks()->attach($stock->id, [
                        'cantidad' => $validated['cantidad'],
                        'precio_unitario' => $precioUnitario,
                    ]);
                    $aumento = self::totalLinea($validated['cantidad'], $precioUnitario);
                    $successMsg = "Repuesto '{$stock->producto}' agregado a la orden.";
                }

                // Sumar al costo del modelo
                $lockedModel->increment('costo', $aumento);

                // B-04 / M-17: la tabla pivote no dispara eventos; se deja constancia explícita.
                Evento::registrar('repuesto_agregado', $lockedModel, null, [
                    'stock_id' => $stock->id,
                    'producto' => $stock->producto,
                    'cantidad' => (int) $validated['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'valor' => $aumento,
                ], "Repuesto {$stock->producto} ×{$validated['cantidad']} agregado a {$lockedModel->id_orden} (\$".number_format($aumento, 0, ',', '.').')');
            });

            return redirect()->back()->with('success', $successMsg);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Error agregando repuesto: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al agregar el repuesto. Intente nuevamente.')->withInput();
        }
    }

    /**
     * Quitar stock de un modelo (Mantenimiento o Electronica)
     *
     * Exige autorización de operación sensible: el administrador actúa sin
     * comprobación y el técnico debe validar la contraseña de un administrador.
     * Esto iguala el criterio con la eliminación de abonos, que también altera
     * existencias y costos.
     */
    protected function detachStock($model, int $stock_id, string $successMsg): RedirectResponse
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible(request())) {
            return redirect()->back()->with('error', $error);
        }

        if ($model->anulado) {
            return redirect()->back()->with('error', 'No se pueden eliminar repuestos de un registro anulado.');
        }
        if ($bloqueo = OrdenEstadoService::bloqueoRepuestos($model)) {
            return redirect()->back()->with('error', $bloqueo);
        }

        try {
            DB::transaction(function () use ($model, $stock_id) {
                // Bloquear el modelo para serializar modificaciones concurrentes
                $lockedModel = get_class($model)::where('id', $model->id)->lockForUpdate()->firstOrFail();
                // B-03: releer tras el bloqueo; otra petición pudo terminar la orden.
                if ($bloqueo = OrdenEstadoService::bloqueoRepuestos($lockedModel)) {
                    throw new \DomainException($bloqueo);
                }
                $repuesto = $lockedModel->stocks()->where('stock_id', $stock_id)->first();

                if (! $repuesto) {
                    throw new \DomainException('El repuesto no existe en este registro.');
                }

                $cantidad = $repuesto->pivot->cantidad;
                $precio = $repuesto->pivot->precio_unitario;
                $valorLinea = self::totalLinea($cantidad, $precio);

                // B-02: quitar el repuesto no puede dejar el costo por debajo de lo ya abonado;
                // eso ocultaba un cobro de más (saldo 0 con dinero recibido de sobra).
                $costoNuevo = round((float) $lockedModel->costo - $valorLinea, 2);
                $abonado = (float) $lockedModel->abonos()->where('anulado', false)->sum('monto');
                if ($abonado > 0.01 && $costoNuevo + 0.01 < $abonado) {
                    throw new \DomainException('No se puede quitar el repuesto: el costo quedaría en $'.number_format(max(0, $costoNuevo), 0, ',', '.')
                        .', menor a lo ya abonado ($'.number_format($abonado, 0, ',', '.').'). Anule primero el abono correspondiente (devolución).');
                }

                // Devolver al stock de forma atómica
                app(StockService::class)->entrada($stock_id, $cantidad);

                // Restar costo del modelo (M-18: nunca por debajo de 0 por redondeos antiguos)
                $lockedModel->decrement('costo', min($valorLinea, max(0, (float) $lockedModel->costo)));

                // Desvincular
                $lockedModel->stocks()->detach($stock_id);

                Evento::registrar('repuesto_quitado', $lockedModel, [
                    'stock_id' => (int) $stock_id,
                    'producto' => $repuesto->producto,
                    'cantidad' => (int) $cantidad,
                    'precio_unitario' => (float) $precio,
                    'valor' => $valorLinea,
                ], null, "Repuesto {$repuesto->producto} ×{$cantidad} quitado de {$lockedModel->id_orden} (stock devuelto)");
            });

            return redirect()->back()->with('success', $successMsg);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error eliminando repuesto: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al eliminar el repuesto. Intente nuevamente.');
        }
    }

    /** Valor de una línea de repuestos redondeado a centavos (M-18). */
    private static function totalLinea($cantidad, $precio): float
    {
        return round((int) $cantidad * (float) $precio, 2);
    }
}
