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

        // M-18: cada línea redondeada a centavos, igual que lo que se sumó al costo.
        return (float) $stocks->sum(fn ($s) => round((int) $s->pivot->cantidad * (float) $s->pivot->precio_unitario, 2));
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
