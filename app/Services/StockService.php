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
}
