<?php

namespace App\Services;

use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     * Valida la autorización para operaciones críticas (anular, reactivar, dar de baja, revertir, eliminar):
     * - Si el usuario es 'admin': autoriza sin requerir contraseña (retorna null).
     * - Si el usuario es 'tecnico': exige y valida contraseña de un administrador activo.
     * - Si es 'invitado' o no autenticado: deniega la acción.
     *
     * @return string|null Mensaje de error si no está autorizado, o null si la operación es permitida.
     */
    public function autorizarOperacionSensible(Request $request): ?string
    {
        $user = Auth::user();
        if (! $user || $user->isInvitado()) {
            return 'No tienes permisos para realizar esta acción.';
        }

        // El Administrador está exento de ingresar contraseña
        if ($user->isAdmin()) {
            return null;
        }

        // El Técnico debe ingresar obligatoriamente la clave de un administrador
        if ($user->isTecnico()) {
            $password = $request->input('admin_password') ?? $request->input('password_confirm');
            if (empty($password)) {
                return 'Se requiere la contraseña de un administrador para autorizar esta operación.';
            }

            if (! $this->adminPasswordValida($password)) {
                return 'Contraseña de administrador incorrecta.';
            }

            return null;
        }

        return 'Rol no autorizado para realizar esta operación.';
    }

    /**
     * Comprueba que la intención enviada por el formulario ('anular' / 'reactivar')
     * coincide con el estado actual del registro. Evita que un doble clic o una
     * pestaña desactualizada inviertan la operación (anular → reactivar).
     *
     * @return string|null Mensaje de error, o null si la operación es coherente.
     */
    public function verificarIntencion(Request $request, bool $esAnulacion): ?string
    {
        $accion = $request->input('accion');
        if ($accion === null || $accion === '') {
            return null; // Formularios sin intención explícita conservan el comportamiento de alternar.
        }

        if (! in_array($accion, ['anular', 'reactivar'], true)) {
            return 'Acción no válida.';
        }

        if (($accion === 'anular') !== $esAnulacion) {
            return $accion === 'anular'
                ? 'El registro ya se encuentra anulado. Recargue la página.'
                : 'El registro ya se encuentra activo. Recargue la página.';
        }

        return null;
    }

    /**
     * Revierte (anulación) o restaura (reactivación) stock y abonos en caja de forma atómica.
     *
     * Al anular, solo se anulan los abonos vigentes y se marcan como anulados en cascada.
     * Al reactivar, solo se restauran los abonos anulados en cascada: los que el usuario
     * eliminó individualmente antes de anular la orden permanecen anulados.
     *
     * @param  Model  $documento  Modelo con relaciones 'stocks' (pivot cantidad) y 'abonos'.
     * @param  bool  $esAnulacion  true = anular (devolver stock, anular caja); false = reactivar.
     * @param  string  $conceptoAbono  Conservado por compatibilidad; los movimientos se localizan por abono_id.
     * @param  string[]  $prefijosDescripcion  Conservado por compatibilidad.
     */
    public function revertirStockYAbonos($documento, bool $esAnulacion, string $conceptoAbono = '', array $prefijosDescripcion = []): void
    {
        DB::transaction(function () use ($documento, $esAnulacion) {
            // Revertir stock asociado al documento de forma atómica
            $stockService = app(StockService::class);
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

            // Revertir abonos registrados en Caja (localizados por abono_id, sin depender del concepto)
            $abonos = $esAnulacion
                ? $documento->abonos()->where('anulado', false)->get()
                : $documento->abonos()->where('anulado', true)->where('anulado_en_cascada', true)->get();

            foreach ($abonos as $abono) {
                $fechaAbono = $abono->fecha ? Carbon::parse($abono->fecha)->toDateString() : null;
                if (CierreCajaGuard::fechaEstaCerrada($fechaAbono)) {
                    throw new \DomainException('No se puede '.($esAnulacion ? 'anular' : 'reactivar')." la orden: tiene un abono registrado el {$fechaAbono}, fecha que ya tiene un cierre de caja registrado. Para modificarla, elimine primero el cierre de ese día.");
                }

                $this->marcarMovimientosCaja($abono, $esAnulacion);
                $abono->update(['anulado' => $esAnulacion, 'anulado_en_cascada' => $esAnulacion]);
            }
        });
    }

    /**
     * Marca los movimientos de caja de un abono uno a uno (no update masivo)
     * para que el trait Auditable registre cada cambio.
     */
    private function marcarMovimientosCaja($abono, bool $esAnulacion): void
    {
        MovimientoCaja::where('abono_id', $abono->id)->get()->each(function (MovimientoCaja $mov) use ($esAnulacion) {
            $mov->update([
                'anulado' => $esAnulacion,
                'estado' => $esAnulacion ? 'anulado' : 'activo',
            ]);
        });
    }
}
