<?php

namespace App\Services;

use App\Models\CierreCaja;
use Illuminate\Support\Facades\Log;

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
     * Determina si una fecha ya tiene cierre de caja registrado.
     */
    public static function fechaEstaCerrada(?string $fecha): bool
    {
        if (empty($fecha)) {
            return false;
        }

        return CierreCaja::whereDate('fecha', $fecha)->exists();
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
                "No se puede registrar {$contexto} con fecha {$fecha}: el día ya tiene cierre de caja registrado. "
                .'Elimine el cierre desde el módulo de Cierre de Caja para reabrir el día.'
            );
        }
    }
}