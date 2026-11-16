<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\Factura;
use App\Models\MovimientoCaja;
use Illuminate\Support\Carbon;

/**
 * Efectivo físico que hay en la caja según el sistema.
 *
 * Una salida en efectivo no puede ser mayor que el efectivo registrado: antes se aceptaba
 * un egreso de $99.999.999 con la caja casi vacía y el efectivo quedaba en negativo, lo que
 * dañaba el arqueo de ese día y de los siguientes. Si el dinero salió de una plata que no
 * estaba registrada, lo correcto es registrar primero ese ingreso (por ejemplo, «Apertura
 * de caja») o usar Consignación.
 */
class EfectivoCaja
{
    /** Efectivo al final del día $fecha (último conteo real + movimientos posteriores). */
    public static function alCierreDe(string $fecha): float
    {
        $siguiente = Carbon::parse($fecha)->addDay()->toDateString();

        return CierreCaja::saldoInicialEfectivo($siguiente);
    }

    /**
     * Mensaje de error si una salida en efectivo de $monto con fecha $fecha deja la caja en
     * negativo; null si alcanza. Se revisa el día del movimiento y también hoy: un egreso con
     * fecha pasada no puede gastar dinero que ya salió después.
     *
     * $devuelto es lo que el propio movimiento ya restaba (al editarlo), para no contarlo dos veces.
     */
    public static function verificarSalida(string $fecha, float $monto, float $devuelto = 0.0): ?string
    {
        $fecha = Carbon::parse($fecha)->toDateString();
        $hoy = now()->toDateString();

        $disponible = self::alCierreDe($fecha);
        if ($fecha < $hoy) {
            $disponible = min($disponible, self::alCierreDe($hoy));
        }
        $disponible += $devuelto;

        if (! Factura::superaMonto($monto, $disponible)) {
            return null;
        }

        $fmt = fn (float $v) => '$ '.number_format($v, 0, ',', '.');

        if ($disponible < 0) {
            return 'No hay suficiente efectivo en caja para esta salida de '.$fmt($monto)
                .'. El efectivo registrado ya está en negativo ('.$fmt($disponible).'): revise y corrija'
                .' en Caja los movimientos en efectivo anteriores antes de registrar más salidas.';
        }

        return 'No hay suficiente efectivo en caja para esta salida de '.$fmt($monto)
            .'. Efectivo registrado: '.$fmt($disponible)
            .'. Si el dinero salió de una plata que no estaba registrada, registre primero ese ingreso'
            .' (por ejemplo, «Apertura de caja») o elija Consignación como medio de pago.';
    }

    /** Lo que un movimiento existente resta hoy del efectivo (0 si no es una salida en efectivo activa). */
    public static function salidaActual(MovimientoCaja $movimiento): float
    {
        if ($movimiento->anulado || $movimiento->estado !== 'activo'
            || $movimiento->tipo_movimiento !== 'egreso' || $movimiento->tipo_pago !== 'efectivo') {
            return 0.0;
        }

        return (float) $movimiento->monto;
    }
}
