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

    // ──────────────────────────────────────────────────────────
    /** Helper: calcula totales de movimientos de un día dado excluyendo los anulados */
    private function calcularDia(string $fecha): array
    {
        $movs = MovimientoCaja::whereDate('fecha', $fecha)->where('estado', 'activo')->get();

        return [
            'total_ingresos'  => $movs->where('tipo_movimiento', 'ingreso')->sum('monto'),
            'total_egresos'   => $movs->where('tipo_movimiento', 'egreso')->sum('monto'),
            'efectivo'        => $movs->where('tipo_pago', 'efectivo')->sum('monto'),
            'consignacion'    => $movs->where('tipo_pago', 'consignacion')->sum('monto'),
            'saldo_final'     => $movs->where('tipo_movimiento', 'ingreso')->sum('monto')
                              - $movs->where('tipo_movimiento', 'egreso')->sum('monto'),
            'num_movimientos' => $movs->count(),
        ];
    }
}
