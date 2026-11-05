<?php

namespace App\Traits;

/**
 * Cálculos comunes de las órdenes (Mantenimiento / Electrónica).
 * El campo `costo` es el total de la orden: mano de obra + repuestos asignados.
 */
trait CalculaCostoOrden
{
    /** Valor de los repuestos asignados a la orden (cantidad × precio unitario de la pivot). */
    public function valorRepuestos(): float
    {
        $stocks = $this->relationLoaded('stocks') ? $this->stocks : $this->stocks()->get();

        return (float) $stocks->sum(fn ($s) => (float) $s->pivot->cantidad * (float) $s->pivot->precio_unitario);
    }

    /**
     * Costo mínimo que puede tener la orden al editarla: nunca por debajo de lo ya
     * abonado (el sobrepago quedaría oculto) ni del valor de sus repuestos.
     */
    public function costoMinimoPermitido(): float
    {
        return round(max($this->total_abonado, $this->valorRepuestos()), 2);
    }
}
