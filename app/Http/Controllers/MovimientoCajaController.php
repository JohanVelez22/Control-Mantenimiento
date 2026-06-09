<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\User;

class MovimientoCajaController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoCaja::with('concepto', 'user');

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }
        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->tipo_pago);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('persona', 'like', "%{$s}%")
                  ->orWhere('empresa', 'like', "%{$s}%");
            });
        }

        $movimientos = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->paginate(15);

        // Totales del período filtrado (sin paginar)
        $totalesQuery = MovimientoCaja::query();
        if ($request->filled('tipo_movimiento')) $totalesQuery->where('tipo_movimiento', $request->tipo_movimiento);
        if ($request->filled('tipo_pago'))       $totalesQuery->where('tipo_pago', $request->tipo_pago);
        if ($request->filled('fecha_desde'))     $totalesQuery->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))     $totalesQuery->whereDate('fecha', '<=', $request->fecha_hasta);
        if ($request->filled('search')) {
            $s = $request->search;
            $totalesQuery->where(function ($q) use ($s) {
                $q->where('persona', 'like', "%{$s}%")->orWhere('empresa', 'like', "%{$s}%");
            });
        }

        $totales = [
            'ingresos'     => (clone $totalesQuery)->where('tipo_movimiento', 'ingreso')->sum('monto'),
            'egresos'      => (clone $totalesQuery)->where('tipo_movimiento', 'egreso')->sum('monto'),
            'efectivo'     => (clone $totalesQuery)->where('tipo_pago', 'efectivo')->sum('monto'),
            'consignacion' => (clone $totalesQuery)->where('tipo_pago', 'consignacion')->sum('monto'),
        ];
        $totales['saldo'] = $totales['ingresos'] - $totales['egresos'];

        $conceptos = ConceptoCaja::orderBy('nombre')->get();

        return view('caja.index', compact('movimientos', 'totales', 'conceptos'));
    }

    public function create()
    {
        $conceptos = ConceptoCaja::orderBy('nombre')->get();
        return view('caja.create', compact('conceptos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'empresa'         => 'nullable|string|max:255',
            'persona'         => 'required|string|max:255',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:255',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01',
            'descripcion'     => 'nullable|string',
        ]);

        // Si ingresaron un nuevo concepto, crearlo o encontrar el existente
        if (!empty($validated['nuevo_concepto'])) {
            $concepto = ConceptoCaja::firstOrCreate(['nombre' => trim($validated['nuevo_concepto'])]);
            $validated['concepto_id'] = $concepto->id;
        }

        $validated['user_id'] = auth()->id();
        unset($validated['nuevo_concepto']);

        $movimiento = MovimientoCaja::create($validated);

        // Redirigir a la vista de impresión si se solicitó
        if ($request->has('print_after')) {
            return redirect()->route('caja.print', $movimiento->id);
        }

        return redirect()->route('caja.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function edit(MovimientoCaja $movimiento)
    {
        $conceptos = ConceptoCaja::orderBy('nombre')->get();
        return view('caja.edit', compact('movimiento', 'conceptos'));
    }

    public function update(Request $request, MovimientoCaja $movimiento)
    {
        $validated = $request->validate([
            'empresa'         => 'nullable|string|max:255',
            'persona'         => 'required|string|max:255',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:255',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01',
            'descripcion'     => 'nullable|string',
        ]);

        if (!empty($validated['nuevo_concepto'])) {
            $concepto = ConceptoCaja::firstOrCreate(['nombre' => trim($validated['nuevo_concepto'])]);
            $validated['concepto_id'] = $concepto->id;
        }

        unset($validated['nuevo_concepto']);
        $movimiento->update($validated);

        return redirect()->route('caja.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    /**
     * Eliminar — requiere contraseña del usuario autenticado o del admin.
     */
    public function destroy(Request $request, MovimientoCaja $movimiento)
    {
        $request->validate([
            'password_confirm' => 'required|string',
        ]);

        $currentUser = auth()->user();

        // Verificar contra la contraseña del usuario actual O de cualquier admin activo
        $passwordOk = Hash::check($request->password_confirm, $currentUser->password);
        if (!$passwordOk) {
            $adminOk = User::where('role', 'admin')->where('active', true)->get()
                ->contains(fn($a) => Hash::check($request->password_confirm, $a->password));
            $passwordOk = $adminOk;
        }

        if (!$passwordOk) {
            return back()->with('error', 'Contraseña incorrecta. No se eliminó el registro.');
        }

        $movimiento->delete();
        return redirect()->route('caja.index')->with('success', 'Movimiento eliminado correctamente.');
    }

    /**
     * Vista de impresión de un movimiento.
     */
    public function print(MovimientoCaja $movimiento)
    {
        $movimiento->load('concepto', 'user');
        return view('caja.print', compact('movimiento'));
    }

    /**
     * API para crear un nuevo concepto dinámicamente (AJAX).
     */
    public function storeConcepto(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:concepto_cajas,nombre']);
        $concepto = ConceptoCaja::create(['nombre' => trim($request->nombre)]);
        return response()->json(['id' => $concepto->id, 'nombre' => $concepto->nombre]);
    }

    /** Duplicar un movimiento de caja (copia con fecha hoy) */
    public function duplicate(MovimientoCaja $movimiento)
    {
        $nuevo = $movimiento->replicate();
        $nuevo->fecha   = now()->toDateString();
        $nuevo->user_id = auth()->id();
        $nuevo->save();

        return redirect()->route('caja.edit', $nuevo)
                         ->with('success', 'Movimiento duplicado. Revisa y actualiza los datos antes de guardar.');
    }
}
