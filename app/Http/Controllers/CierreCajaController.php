<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\CierreCaja;
use App\Models\MovimientoCaja;
use App\Models\User;

class CierreCajaController extends Controller
{
    public function index()
    {
        $cierres = CierreCaja::with('user')->orderBy('fecha', 'desc')->paginate(10);

        // Pre-calcular datos del día actual (aún no cerrado)
        $hoy = now()->toDateString();
        $yaExiste = CierreCaja::whereDate('fecha', $hoy)->exists();

        $preview = null;
        if (!$yaExiste) {
            $preview = $this->calcularDia($hoy);
        }

        return view('cierre.index', compact('cierres', 'preview', 'hoy', 'yaExiste'));
    }

    /** Realiza el cierre del día indicado */
    public function store(Request $request)
    {
        $request->validate([
            'fecha'        => 'required|date|unique:cierre_cajas,fecha',
            'observaciones'=> 'nullable|string|max:1000',
        ]);

        $datos = $this->calcularDia($request->fecha);

        CierreCaja::create([
            'fecha'           => $request->fecha,
            'total_ingresos'  => $datos['total_ingresos'],
            'total_egresos'   => $datos['total_egresos'],
            'efectivo'        => $datos['efectivo'],
            'consignacion'    => $datos['consignacion'],
            'saldo_final'     => $datos['saldo_final'],
            'num_movimientos' => $datos['num_movimientos'],
            'bloqueado'       => true,
            'observaciones'   => $request->observaciones,
            'user_id'         => auth()->id(),
        ]);

        return redirect()->route('cierre.index')
                         ->with('success', "Cierre del " . \Carbon\Carbon::parse($request->fecha)->format('d/m/Y') . " guardado y bloqueado.");
    }

    /** Eliminar cierre — requiere contraseña */
    public function destroy(Request $request, CierreCaja $cierre)
    {
        $request->validate(['password_confirm' => 'required|string']);

        $currentUser = auth()->user();
        $ok = Hash::check($request->password_confirm, $currentUser->password);
        if (!$ok) {
            $ok = User::where('role', 'admin')->where('active', true)->get()
                      ->contains(fn($a) => Hash::check($request->password_confirm, $a->password));
        }

        if (!$ok) {
            return back()->with('error', 'Contraseña incorrecta. El cierre no fue eliminado.');
        }

        $cierre->delete();
        return redirect()->route('cierre.index')->with('success', 'Cierre eliminado y día desbloqueado.');
    }

    /** Ver detalle del cierre de caja */
    public function show(CierreCaja $cierre)
    {
        $cierre->load('user');
        $fecha = $cierre->fecha->toDateString();

        $movimientos = MovimientoCaja::with(['user', 'concepto'])
            ->whereDate('fecha', $fecha)
            ->orderBy('id', 'desc')
            ->get();

        return view('cierre.show', compact('cierre', 'movimientos'));
    }

    /** Formulario para editar observaciones del cierre */
    public function edit(CierreCaja $cierre)
    {
        return view('cierre.edit', compact('cierre'));
    }

    /** Actualizar observaciones del cierre */
    public function update(Request $request, CierreCaja $cierre)
    {
        $request->validate([
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cierre->update([
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('cierre.show', $cierre->id)
                         ->with('success', 'Observaciones del cierre actualizadas correctamente.');
    }

    // ──────────────────────────────────────────────────────────
    /** Helper: calcula totales de movimientos de un día dado excluyendo los anulados */
    private function calcularDia(string $fecha): array
    {
        $movs = MovimientoCaja::whereDate('fecha', $fecha)->where('estado', 'activo')->where('anulado', false)->get();

        $ingresos = (float) $movs->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos  = (float) $movs->where('tipo_movimiento', 'egreso')->sum('monto');

        $efectivoIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoEgresos  = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'efectivo')->sum('monto');
        $efectivoNeto     = $efectivoIngresos - $efectivoEgresos;

        $consignacionIngresos = (float) $movs->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionEgresos  = (float) $movs->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'consignacion')->sum('monto');
        $consignacionNeto     = $consignacionIngresos - $consignacionEgresos;

        return [
            'total_ingresos'  => $ingresos,
            'total_egresos'   => $egresos,
            'efectivo'        => $efectivoNeto,
            'consignacion'    => $consignacionNeto,
            'saldo_final'     => $ingresos - $egresos,
            'num_movimientos' => $movs->count(),
        ];
    }
}
