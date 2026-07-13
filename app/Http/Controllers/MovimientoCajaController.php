<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovimientoCajaController extends Controller
{
    public function index(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde', date('Y-m-01'));
        $fecha_hasta = $request->input('fecha_hasta', date('Y-m-d'));

        // Sincroniza con el request para que Blade coincida
        $request->merge([
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
        ]);

        $query = MovimientoCaja::with('concepto', 'user');

        if ($request->filled('tipo_movimiento') && $request->tipo_movimiento !== 'todos') {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }
        if ($request->filled('tipo_pago') && $request->tipo_pago !== 'todos') {
            $query->where('tipo_pago', $request->tipo_pago);
        }
        
        $query->whereDate('fecha', '>=', $fecha_desde);
        $query->whereDate('fecha', '<=', $fecha_hasta);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('persona', 'like', "%{$s}%")
                  ->orWhere('empresa', 'like', "%{$s}%")
                  ->orWhere('descripcion', 'like', "%{$s}%")
                  ->orWhereHas('concepto', function($q2) use ($s) {
                      $q2->where('nombre', 'like', "%{$s}%");
                  });
            });
        }

        $movimientos = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->paginate(10);

        // Totales del período filtrado (sin paginar) — EXCLUYE anulados
        $totalesQuery = MovimientoCaja::where('estado', 'activo')->where('anulado', false);
        if ($request->filled('tipo_movimiento') && $request->tipo_movimiento !== 'todos') $totalesQuery->where('tipo_movimiento', $request->tipo_movimiento);
        if ($request->filled('tipo_pago') && $request->tipo_pago !== 'todos')       $totalesQuery->where('tipo_pago', $request->tipo_pago);
        
        $totalesQuery->whereDate('fecha', '>=', $fecha_desde);
        $totalesQuery->whereDate('fecha', '<=', $fecha_hasta);

        if ($request->filled('search')) {
            $s = $request->search;
            $totalesQuery->where(function ($q) use ($s) {
                $q->where('persona', 'like', "%{$s}%")
                  ->orWhere('empresa', 'like', "%{$s}%")
                  ->orWhere('descripcion', 'like', "%{$s}%")
                  ->orWhereHas('concepto', function($q2) use ($s) {
                      $q2->where('nombre', 'like', "%{$s}%");
                  });
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
            'empresa'         => 'nullable|string|max:80',
            'persona'         => 'nullable|string|max:80',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|integer|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:80',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01|decimal:0,2',
            'monto_total'     => 'nullable|numeric|min:0|decimal:0,2',
            'descripcion'     => 'nullable|string|max:500',
        ]);

        // Validar que al menos empresa o persona esté presente, pero NO ambas
        if (empty($validated['empresa']) && empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'Debe indicar al menos un nombre de persona o empresa.'])->withInput();
        }
        if (!empty($validated['empresa']) && !empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'No puede ingresar "Persona" y "Empresa" al mismo tiempo. Elija solo uno.'])->withInput();
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
        $movimiento->load('childPayments.user');
        $conceptos = ConceptoCaja::orderBy('nombre')->get();
        return view('caja.edit', compact('movimiento', 'conceptos'));
    }

    public function update(Request $request, MovimientoCaja $movimiento)
    {
        $validated = $request->validate([
            'empresa'         => 'nullable|string|max:80',
            'persona'         => 'nullable|string|max:80',
            'fecha'           => 'required|date',
            'concepto_id'     => 'required_without:nuevo_concepto|nullable|integer|exists:concepto_cajas,id',
            'nuevo_concepto'  => 'required_without:concepto_id|nullable|string|max:80',
            'tipo_movimiento' => 'required|in:ingreso,egreso',
            'tipo_pago'       => 'required|in:efectivo,consignacion',
            'monto'           => 'required|numeric|min:0.01|decimal:0,2',
            'monto_total'     => 'nullable|numeric|min:0|decimal:0,2',
            'descripcion'     => 'nullable|string|max:500',
        ]);

        // Validar que al menos empresa o persona esté presente, pero NO ambas
        if (empty($validated['empresa']) && empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'Debe indicar al menos un nombre de persona o empresa.'])->withInput();
        }
        if (!empty($validated['empresa']) && !empty($validated['persona'])) {
            return back()->withErrors(['persona' => 'No puede ingresar "Persona" y "Empresa" al mismo tiempo. Elija solo uno.'])->withInput();
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
     * Vista de impresión de un movimiento.
     */
    public function print(MovimientoCaja $movimiento)
    {
        $movimiento->load('concepto', 'user');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('caja.print', compact('movimiento'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('comprobante_caja_' . $movimiento->id . '.pdf');
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

    /** Registrar un abono/pago parcial a este movimiento */
    public function storeAbono(Request $request, MovimientoCaja $movimiento)
    {
        $validated = $request->validate([
            'monto_abono' => 'required|numeric|min:0.01',
            'fecha'       => 'required|date',
            'tipo_pago'   => 'required|in:efectivo,consignacion',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if ($validated['monto_abono'] > $movimiento->saldo_pendiente) {
            return back()->with('error', 'El abono supera el saldo pendiente de $' . number_format($movimiento->saldo_pendiente, 0, ',', '.') . '.');
        }

        try {
            DB::beginTransaction();

            MovimientoCaja::create([
                'empresa'         => $movimiento->empresa,
                'persona'         => $movimiento->persona,
                'fecha'           => $validated['fecha'],
                'concepto_id'     => $movimiento->concepto_id,
                'tipo_movimiento' => $movimiento->tipo_movimiento,
                'tipo_pago'       => $validated['tipo_pago'],
                'monto'           => $validated['monto_abono'],
                'monto_total'     => 0, // Los abonos parciales no tienen total propio
                'descripcion'     => $validated['descripcion'] ?: 'Abono parcial a saldo de movimiento #' . $movimiento->id,
                'estado'          => 'activo',
                'user_id'         => auth()->id(),
                'parent_id'       => $movimiento->id,
            ]);

            DB::commit();

            return back()->with('success', 'Abono de $' . number_format($validated['monto_abono'], 0, ',', '.') . ' registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando abono de caja: ' . $e->getMessage());
            return back()->with('error', 'Error al registrar el abono. Intente nuevamente.');
        }
    }

    public function anular(Request $request, MovimientoCaja $movimiento)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        // Técnico requiere contraseña de admin; admin usa su propia o la de admin.
        if (auth()->user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para anular.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.');
            }
        }

        try {
            DB::beginTransaction();
            $esAnulacion = !$movimiento->anulado;
            
            // Anular movimiento principal
            $movimiento->update(['anulado' => $esAnulacion]);
            
            // Anular o restaurar todos los abonos asociados en cascada
            $movimiento->childPayments()->update(['anulado' => $esAnulacion]);

            DB::commit();
            return redirect()->back()->with('success', $esAnulacion ? 'Movimiento y sus abonos anulados correctamente.' : 'Movimiento y sus abonos reactivados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando movimiento de caja: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al anular el movimiento de caja.');
        }
    }
}
