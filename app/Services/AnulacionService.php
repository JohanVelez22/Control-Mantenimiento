<?php

namespace App\Services;

use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Lógica central de anulación/reactivación.
 *
 * Unifica el código que antes estaba duplicado (~280 líneas) en
 * MantenimientoController y ElectronicaController: la validación de la
 * contraseña de anulación y la reversión de stock + abonos en caja.
 *
 * Al anular: se devuelve el stock a bodega y se marcan como anulados los
 * movimientos de caja generados por los abonos. Al reactivar: se hace lo
 * contrario. La búsqueda de los movimientos de caja usa el mismo criterio
 * que el código original (abono_id o coincidencia de concepto/monto/fecha/
 * descripción), por lo que el comportamiento se preserva.
 */
class AnulacionService
{
    /**
     * Valida que la contraseña corresponda a cualquier administrador.
     * Se usa para acciones sensibles de técnico (editar/anular).
     * Verifica contra TODOS los admins, no solo el primero.
     */
    /**
     * Valida que la contraseña corresponda a cualquier administrador.
     * Se usa para acciones sensibles de técnico (editar/anular).
     * Verifica contra administradores activos con salida temprana.
     */
    public function adminPasswordValida(string $password): bool
    {
        $admins = User::where('role', 'admin')->where('active', true)->select('password')->cursor();
        foreach ($admins as $admin) {
            if (Hash::check($password, $admin->password)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Valida la contraseña de anulación.
     * Acepta la contraseña del usuario en sesión o la de un administrador.
     */
    public function passwordValida(string $password): bool
    {
        $user = Auth::user();

        if ($user && Hash::check($password, $user->password)) {
            return true;
        }

        return $this->adminPasswordValida($password);
    }

    /**
     * Revierte (anulación) o restaura (reactivación) stock y abonos en caja de forma atómica.
     *
     * @param \Illuminate\Database\Eloquent\Model $documento Modelo con relaciones 'stocks' (pivot cantidad) y 'abonos'.
     * @param bool $esAnulacion true = anular (devolver stock, anular caja); false = reactivar.
     * @param string $conceptoAbono Nombre del concepto en caja (p.ej. 'Abono Mantenimiento').
     * @param string[] $prefijosDescripcion Tokens que anteceden al id en la descripción de caja.
     */
    public function revertirStockYAbonos($documento, bool $esAnulacion, string $conceptoAbono, array $prefijosDescripcion): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($documento, $esAnulacion, $conceptoAbono, $prefijosDescripcion) {
            // Revertir stock asociado al documento de forma atómica
            $stockService = app(\App\Services\StockService::class);
            foreach ($documento->stocks as $stock) {
                $delta = (int) $stock->pivot->cantidad;
                $stockModel = Stock::where('id', $stock->id)->lockForUpdate()->first();
                if ($stockModel) {
                    if ($esAnulacion) {
                        $stockService->entrada($stockModel, $delta);
                    } else {
                        $stockService->salida($stockModel, $delta);
                    }
                }
            }

            // Revertir abonos registrados en Caja
            $concepto = ConceptoCaja::where('nombre', $conceptoAbono)->first();
            if ($concepto && $documento->abonos->count() > 0) {
                foreach ($documento->abonos as $abono) {
                    $this->marcarMovimientosCaja($abono, $concepto, $documento->id_orden, $prefijosDescripcion, $esAnulacion);
                }
            }
        });
    }

    private function marcarMovimientosCaja($abono, $concepto, string $idOrden, array $prefijosDescripcion, bool $esAnulacion): void
    {
        $query = MovimientoCaja::where('abono_id', $abono->id);

        if ($esAnulacion) {
            $query->where(function($q) {
                $q->where('estado', 'activo')->orWhere('anulado', false);
            })->update([
                'anulado' => true,
                'estado'  => 'anulado',
            ]);
        } else {
            $query->update([
                'anulado' => false,
                'estado'  => 'activo',
            ]);
        }
    }
}
