<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Totales del período filtrado (sin paginar) — EXCLUYE anulados
        $totalesQuery = MovimientoCaja::where('estado', 'activo');
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
            'persona'         => 'nullable|string|max:255',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:255',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01',
            'descripcion'     => 'nullable|string',
        ]);

        // Validar que al menos empresa o persona esté presente
        if (empty($validated['empresa']) && empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'Debe indicar al menos un nombre de persona o empresa.'])->withInput();
        }

        try {
            DB::beginTransaction();
            // Si ingresaron un nuevo concepto, crearlo o encontrar el existente
            if (!empty($validated['nuevo_concepto'])) {
                $concepto = ConceptoCaja::firstOrCreate(['nombre' => trim($validated['nuevo_concepto'])]);
                $validated['concepto_id'] = $concepto->id;
            }

            $validated['user_id'] = auth()->id();
            unset($validated['nuevo_concepto']);

            $movimiento = MovimientoCaja::create($validated);
            DB::commit();

            // Redirigir a la vista de impresión si se solicitó
            if ($request->has('print_after')) {
                return redirect()->route('caja.print', $movimiento->id);
            }

            return redirect()->route('caja.index')->with('success', 'Movimiento registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando movimiento de caja: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al guardar el movimiento de caja.')->withInput();
        }
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
            'persona'         => 'nullable|string|max:255',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:255',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01',
            'descripcion'     => 'nullable|string',
        ]);

        // Validar que al menos empresa o persona esté presente
        if (empty($validated['empresa']) && empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'Debe indicar al menos un nombre de persona o empresa.'])->withInput();
        }

        try {
            DB::beginTransaction();
            if (!empty($validated['nuevo_concepto'])) {
                $concepto = ConceptoCaja::firstOrCreate(['nombre' => trim($validated['nuevo_concepto'])]);
                $validated['concepto_id'] = $concepto->id;
            }

            unset($validated['nuevo_concepto']);
            $movimiento->update($validated);
            DB::commit();

            return redirect()->route('caja.index')->with('success', 'Movimiento actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando movimiento de caja: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el movimiento de caja.')->withInput();
        }
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

        try {
            DB::beginTransaction();
            $movimiento->delete();
            DB::commit();
            return redirect()->route('caja.index')->with('success', 'Movimiento eliminado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando movimiento de caja: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el movimiento de caja.');
        }
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

    public function anular(Request $request, MovimientoCaja $movimiento)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        $request->validate([
            'password_confirm' => 'required'
        ]);

        $currentUser = auth()->user();
        $ok = Hash::check($request->password_confirm, $currentUser->password);
        if (!$ok) {
            $adminPassword = User::where('role', 'admin')->value('password');
            $ok = $adminPassword && Hash::check($request->password_confirm, $adminPassword);
        }

        if (!$ok) {
            return redirect()->back()->with('error', 'Contraseña incorrecta.');
        }

        try {
            DB::beginTransaction();
            $movimiento->update(['estado' => 'anulado']);
            DB::commit();
            return redirect()->back()->with('success', 'Movimiento anulado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando movimiento de caja: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al anular el movimiento de caja.');
        }
    }
}
