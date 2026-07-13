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
     * Valida que la contraseña corresponda a un administrador (no a la del
     * usuario en sesión). Se usa para acciones sensibles de técnico (editar).
     */
    public function adminPasswordValida(string $password): bool
    {
        $adminPassword = User::where('role', 'admin')->value('password');
        return $adminPassword && Hash::check($password, $adminPassword);
    }

    /**
     * Valida la contraseña de anulación.
     * Acepta la contraseña del usuario en sesión o la de un administrador.
     */
    public function passwordValida(string $password): bool
    {
        $user = Auth::user();

        if (Hash::check($password, $user->password)) {
            return true;
        }

        $adminPassword = User::where('role', 'admin')->value('password');
        return $adminPassword && Hash::check($password, $adminPassword);
    }

    /**
     * Revierte (anulación) o restaura (reactivación) stock y abonos en caja.
     *
     * @param \Illuminate\Database\Eloquent\Model $documento Modelo con relaciones 'stocks' (pivot cantidad) y 'abonos'.
     * @param bool $esAnulacion true = anular (devolver stock, anular caja); false = reactivar.
     * @param string $conceptoAbono Nombre del concepto en caja (p.ej. 'Abono Mantenimiento').
     * @param string[] $prefijosDescripcion Tokens que anteceden al id en la descripción de caja (p.ej. ['Orden'] o ['ELC','Orden']).
     */
    public function revertirStockYAbonos($documento, bool $esAnulacion, string $conceptoAbono, array $prefijosDescripcion): void
    {
        // Revertir stock asociado al documento
        foreach ($documento->stocks as $stock) {
            $delta = $stock->pivot->cantidad;
            if ($esAnulacion) {
                Stock::where('id', $stock->id)->increment('cantidad', $delta);
            } else {
                Stock::where('id', $stock->id)->decrement('cantidad', $delta);
            }
        }

        // Revertir abonos registrados en Caja
        $concepto = ConceptoCaja::where('nombre', $conceptoAbono)->first();
        if ($concepto && $documento->abonos->count() > 0) {
            foreach ($documento->abonos as $abono) {
                $this->marcarMovimientosCaja($abono, $concepto, $documento->id_orden, $prefijosDescripcion, $esAnulacion);
            }
        }
    }

    private function marcarMovimientosCaja($abono, $concepto, string $idOrden, array $prefijosDescripcion, bool $esAnulacion): void
    {
        $query = MovimientoCaja::where(function ($q) use ($abono, $concepto, $idOrden, $prefijosDescripcion) {
            $q->where('abono_id', $abono->id)
              ->orWhere(function ($sub) use ($abono, $concepto, $idOrden, $prefijosDescripcion) {
                  $sub->whereNull('abono_id')
                      ->where('concepto_id', $concepto->id)
                      ->where('monto', $abono->monto)
                      ->where('fecha', $abono->fecha->toDateString())
                      ->where(function ($descQuery) use ($idOrden, $prefijosDescripcion) {
                          foreach ($prefijosDescripcion as $prefijo) {
                              $descQuery->orWhere('descripcion', 'like', "%{$prefijo} " . $idOrden . "%");
                          }
                      });
              });
        });

        // Al anular solo se afectan movimientos activos; al reactivar, todos.
        if ($esAnulacion) {
            $query->where('estado', 'activo');
        }

        $query->update(['anulado' => $esAnulacion]);
    }
}
