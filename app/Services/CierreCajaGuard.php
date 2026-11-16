<?php

namespace App\Services;

use App\Models\CierreCaja;
use Illuminate\Support\Facades\DB;

/**
 * Guardián de periods de caja cerrados.
 *
 * Un "cierre de caja" congela el snapshot del día para el arqueo histórico.
 * Sin este control, es posible cerrar el día y seguir registrando movimientos
 * sobre esa misma fecha: el cierre queda obsoleto en silencio y la interfaz
 * sigue mostrando "DÍA BLOQUEADO", cuando el saldo real ya no coincide.
 */
class CierreCajaGuard
{
    /**
     * Una fecha está cerrada si ella o CUALQUIER día posterior tiene cierre (M-06): un
     * movimiento retroactivo en un día abierto anterior cambiaría el saldo inicial de
     * efectivo de los cierres ya hechos sin que nadie lo note.
     */
    public static function fechaEstaCerrada(?string $fecha): bool
    {
        if (empty($fecha)) {
            return false;
        }

        $consulta = CierreCaja::whereDesdeDia('fecha', $fecha);

        // D-08: dentro de una transacción, lectura bloqueante compartida. Si en ese momento
        // se está guardando un cierre de esa fecha, se espera a que termine y se ve; y un
        // cierre que empiece después espera a que esta operación confirme, y la incluye.
        if (DB::transactionLevel() > 0) {
            $consulta->sharedLock();
        }

        return $consulta->exists();
    }

    /**
     * Lanza excepción si la fecha está cerrada. Para uso dentro de transacciones
     * donde no se puede devolver una respuesta de validación al usuario.
     *
     * @throws \DomainException
     */
    public static function asegurarAbierta(string $fecha, string $contexto = 'movimiento de caja'): void
    {
        if (self::fechaEstaCerrada($fecha)) {
            throw new \DomainException(
                "No se puede registrar {$contexto} con fecha {$fecha}: ese día (o uno posterior) ya tiene cierre de caja registrado. "
                .'Registre la operación con la fecha de hoy o elimine el cierre correspondiente para reabrir el período.'
            );
        }
    }
}
