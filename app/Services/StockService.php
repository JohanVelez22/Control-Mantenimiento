<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos de stock atómicos (entrada/salida).
 *
 * Riesgo original: leer $stock->cantidad (carga previa) y luego hacer
 * ->decrement(). Bajo concurrencia dos salidas podían sobrepasar el saldo
 * real y dejar cantidad negativa. Aquí se bloquea la fila con lockForUpdate
 * dentro de una transacción y se re-lee el valor actual antes de validar
 * y aplicar el cambio, eliminando la condición de carrera.
 */
class StockService
{
    /**
     * Entrada de stock (compra o devolución).
     *
     * @param Stock|int $stock Modelo o ID del artículo.
     * @param int $cantidad Unidades a ingresar (debe ser > 0).
     * @return Stock Modelo actualizado.
     */
    public function entrada(Stock|int $stock, int $cantidad): Stock
    {
        if ($cantidad <= 0) {
            throw new \DomainException('La cantidad de entrada debe ser mayor que cero.');
        }

        $id = $stock instanceof Stock ? $stock->id : $stock;

        return DB::transaction(function () use ($id, $cantidad) {
            $model = Stock::lockForUpdate()->findOrFail($id);
            $model->increment('cantidad', $cantidad);
            return $model->refresh();
        });
    }

    /**
     * Salida de stock (venta o repuesto). Lanza DomainException si no alcanza.
     *
     * @param Stock|int $stock Modelo o ID del artículo.
     * @param int $cantidad Unidades a retirar (debe ser > 0).
     * @return Stock Modelo actualizado.
     */
    public function salida(Stock|int $stock, int $cantidad): Stock
    {
        if ($cantidad <= 0) {
            throw new \DomainException('La cantidad de salida debe ser mayor que cero.');
        }

        $id = $stock instanceof Stock ? $stock->id : $stock;

        return DB::transaction(function () use ($id, $cantidad) {
            $model = Stock::lockForUpdate()->findOrFail($id);

            if ($model->cantidad < $cantidad) {
                throw new \DomainException(
                    "Stock insuficiente para '{$model->producto}'. Disponible: {$model->cantidad}, solicitado: {$cantidad}."
                );
            }

            $model->decrement('cantidad', $cantidad);
            return $model->refresh();
        });
    }

    /**
     * Da de baja unidades de stock por daño, defecto o merma de forma atómica.
     *
     * @param Stock|int $stock Modelo o ID del artículo.
     * @param int $cantidad Unidades a descartar (debe ser > 0 y <= stock disponible).
     * @param string $motivo Causa de la baja (defectuoso_fabrica, dano_taller, etc.).
     * @param string|null $observacion Justificación técnica o detalle.
     * @param int|null $userId Usuario que autoriza/registra la baja.
     * @return \App\Models\BajaStock Registro de auditoría de la baja creada.
     */
    public function darDeBaja(Stock|int $stock, int $cantidad, string $motivo, ?string $observacion = null, ?int $userId = null): \App\Models\BajaStock
    {
        if ($cantidad <= 0) {
            throw new \DomainException('La cantidad a dar de baja debe ser mayor que cero.');
        }

        $id = $stock instanceof Stock ? $stock->id : $stock;

        return DB::transaction(function () use ($id, $cantidad, $motivo, $observacion, $userId) {
            $model = Stock::lockForUpdate()->findOrFail($id);

            if ($model->cantidad < $cantidad) {
                throw new \DomainException(
                    "No es posible dar de baja {$cantidad} unidades. Stock disponible actual: {$model->cantidad}."
                );
            }

            $model->decrement('cantidad', $cantidad);

            $precioCompra = (float) $model->precio_compra;
            $costoPerdida = $cantidad * $precioCompra;

            return \App\Models\BajaStock::create([
                'stock_id'               => $model->id,
                'user_id'                => $userId ?? auth()->id(),
                'cantidad'               => $cantidad,
                'precio_compra_unitario' => $precioCompra,
                'costo_total_perdida'    => $costoPerdida,
                'motivo'                 => $motivo,
                'observacion'            => $observacion,
            ]);
        });
    }

    /**
     * Revierte una baja de stock previamente registrada:
     * - Restituye las unidades al inventario disponible.
     * - Elimina el registro de baja (auditable mediante el trait Auditable).
     */
    public function revertirBaja(\App\Models\BajaStock|int $bajaStock, ?int $userId = null): void
    {
        $id = $bajaStock instanceof \App\Models\BajaStock ? $bajaStock->id : $bajaStock;

        DB::transaction(function () use ($id) {
            $baja = \App\Models\BajaStock::lockForUpdate()->findOrFail($id);
            $stock = Stock::lockForUpdate()->findOrFail($baja->stock_id);

            // Restituir las unidades al stock disponible
            $stock->increment('cantidad', $baja->cantidad);

            // Eliminar el registro de baja
            $baja->delete();
        });
    }
}
