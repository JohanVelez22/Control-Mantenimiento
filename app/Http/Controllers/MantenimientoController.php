<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Tecnico;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\MantenimientosExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MantenimientoController extends Controller
{
    /**
     * Muestra la lista de todos los mantenimientos registrados.
     * Carga las relaciones (equipo, cliente, técnico, usuario) para evitar múltiples consultas a la DB.
     */
    public function index(Request $request)
    {
        if ($request->has('locate')) {
            $id = $request->locate;
            // Calcular la página asumiendo orden descendente por ID
            $position = Mantenimiento::where('id', '>=', $id)->count();
            $page = ceil($position / 10) ?: 1;
            return redirect()->route('mantenimientos.index', ['page' => $page])->withFragment('mantenimiento-' . $id);
        }

        $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user', 'abonos'])->orderBy('id', 'desc')->paginate(10);
        return view('mantenimientos.index', compact('mantenimientos'));
    }



    public function show(Mantenimiento $mantenimiento)
    {
        $mantenimiento->load(['equipo.cliente', 'tecnico', 'user', 'abonos.user', 'stocks']);
        $stocks_disponibles = \App\Models\Stock::where('cantidad', '>', 0)->orderBy('producto')->get();
        return view('mantenimientos.show', compact('mantenimiento', 'stocks_disponibles'));
    }

    /** Duplicar un mantenimiento existente como nuevo borrador */
    public function duplicate(Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('mantenimientos.index')->with('error', 'Sin permisos para duplicar.');
        }

        $ultimo = Mantenimiento::orderByDesc('id')->first();
        $siguiente = $ultimo ? intval(preg_replace('/[^0-9]/', '', $ultimo->id_orden)) + 1 : 1;

        $nuevo = $mantenimiento->replicate();
        $nuevo->id_orden     = 'ORD-' . $siguiente;
        $nuevo->fecha_entrada = now()->toDateString();
        $nuevo->fecha_salida  = null;
        $nuevo->estado        = 'pendiente';
        $nuevo->user_id       = Auth::id();
        $nuevo->save();

        return redirect()->route('mantenimientos.edit', $nuevo)
                         ->with('success', "Mantenimiento duplicado como {$nuevo->id_orden}. Revisa y guarda los cambios.");
    }

    /**
     * Módulo de reportes con filtros dinámicos.
     * Permite filtrar por fechas, clientes, equipos, técnicos y estados.
     */
    public function reportes(Request $request)
    {
        $fecha_desde = $request->input('fecha_desde', date('Y-m-01'));
        $fecha_hasta = $request->input('fecha_hasta', date('Y-m-d'));

        // Merge back to request so Blade matches
        $request->merge([
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
        ]);

        $query = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user']);

        // Aplicación de filtros según los parámetros recibidos en el request
        $query->whereDate('fecha_entrada', '>=', $fecha_desde);
        $query->whereDate('fecha_entrada', '<=', $fecha_hasta);
        
        // Filtro complejo: Buscar mantenimientos de equipos que pertenecen a un cliente específico
        if ($request->filled('cliente_id') && $request->cliente_id !== 'todos') {
            $query->whereHas('equipo', function($q) use ($request) { $q->where('cliente_id', $request->cliente_id); });
        }
        
        if ($request->filled('equipo_id') && $request->equipo_id !== 'todos') $query->where('equipo_id', $request->equipo_id);
        if ($request->filled('tecnico_id') && $request->tecnico_id !== 'todos') $query->where('tecnico_id', $request->tecnico_id);
        if ($request->filled('user_id') && $request->user_id !== 'todos') $query->where('user_id', $request->user_id);
        if ($request->filled('tipo_rep') && $request->tipo_rep !== 'todos') {
            $val = $request->tipo_rep;
            if (in_array($val, ['preventivo', 'correctivo'])) {
                $query->where('tipo', $val);
            } elseif (in_array($val, ['software', 'hardware'])) {
                $query->where('reparacion', $val);
            }
        } else {
            if ($request->filled('tipo') && $request->tipo !== 'todos') $query->where('tipo', $request->tipo);
            if ($request->filled('reparacion') && $request->reparacion !== 'todos') $query->where('reparacion', $request->reparacion);
        }
        if ($request->filled('estado') && $request->estado !== 'todos') $query->where('estado', $request->estado);
        if ($request->filled('anulado') && $request->anulado !== 'todos') {
            if ($request->anulado === 'activo') {
                $query->where(function($q) {
                    $q->where('anulado', 0)->orWhereNull('anulado');
                });
            } elseif ($request->anulado === 'anulado') {
                $query->where('anulado', 1);
            }
        }
        if ($request->filled('min_cost')) $query->where('costo', '>=', $request->min_cost);
        if ($request->filled('max_cost')) $query->where('costo', '<=', $request->max_cost);

        // Búsqueda general
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id_orden', 'like', "%{$search}%")
                  ->orWhereHas('equipo', function($q2) use ($search) {
                      $q2->where('nombre', 'like', "%{$search}%")
                         ->orWhere('marca', 'like', "%{$search}%")
                         ->orWhere('modelo', 'like', "%{$search}%")
                         ->orWhere('serie', 'like', "%{$search}%")
                         ->orWhereHas('cliente', function($q3) use ($search) {
                             $q3->where('nombre', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Lógica de exportación o paginación según el botón presionado
        if ($request->get('export') == 'excel') {
            $mantenimientos = $query->orderBy('id', 'desc')->get();
            return \Maatwebsite\Excel\Facades\Excel::download(new MantenimientosExport($mantenimientos), 'Reporte_Mantenimientos_' . date('Y-m-d_His') . '.xlsx');
        }
        if ($request->get('export') == 'pdf') {
            $mantenimientos = $query->orderBy('id', 'desc')->get();
            return Pdf::loadView('mantenimientos.pdf', compact('mantenimientos'))
                ->setPaper('a4', 'portrait')
                ->download('Reporte_Mantenimientos_' . date('Y-m-d_His') . '.pdf');
        }

        $mantenimientos = $query->orderBy('id', 'desc')->paginate(10);

        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre', 'identificacion']);
        $equipos  = Equipo::orderBy('nombre')->get(['id', 'nombre', 'modelo', 'serie']);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('mantenimientos.reportes', compact('mantenimientos', 'clientes', 'equipos', 'tecnicos', 'usuarios'));
    }

    public function create()
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('mantenimientos.index')->with('error', 'No tienes permisos para crear.');
        }
        $equipos  = Equipo::with('cliente')->orderBy('nombre')->get();
        $tecnicos = Tecnico::orderBy('nombre')->get();

        // Calcular el siguiente consecutivo para mostrarlo en el formulario
        $ultimo     = Mantenimiento::orderByDesc('id')->first();
        $siguiente  = $ultimo ? intval(preg_replace('/[^0-9]/', '', $ultimo->id_orden)) + 1 : 1;
        $nextOrden  = 'ORD-' . $siguiente;

        return view('mantenimientos.create', compact('equipos', 'tecnicos', 'nextOrden'));
    }

    /**
     * Guarda un nuevo mantenimiento en la base de datos.
     * Genera automáticamente el número de orden secuencial (ORD-X).
     */
    public function store(Request $request)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('mantenimientos.index')->with('error', 'No tienes permisos para crear.');
        }

        $validated = $request->validate([
            'fecha_entrada' => 'required|date',
            'fecha_salida'  => 'nullable|date|after_or_equal:fecha_entrada',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string|max:500',
            'costo' => 'required|numeric|min:0|decimal:0,2',
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|integer|exists:equipos,id',
            'tecnico_id' => 'required|integer|exists:tecnicos,id',
        ]);

        try {
            DB::beginTransaction();

            // Bloqueo pesimista: garantiza que solo UN request a la vez
            // pueda leer el último id_orden e incrementarlo.
            // Esto elimina el race condition en creación simultánea de órdenes.
            $ultimo = Mantenimiento::lockForUpdate()->orderByDesc('id')->first();
            $siguiente = $ultimo ? intval(preg_replace('/[^0-9]/', '', $ultimo->id_orden)) + 1 : 1;
            $validated['id_orden'] = 'ORD-' . $siguiente;
            $validated['user_id'] = Auth::id();

            Mantenimiento::create($validated);
            DB::commit();
            return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el mantenimiento. Intente nuevamente.')->withInput();
        }
    }

    public function edit(Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('mantenimientos.index')->with('error', 'No tienes permisos para editar.');
        }
        $equipos = Equipo::all();
        $tecnicos = Tecnico::all();
        return view('mantenimientos.edit', compact('mantenimiento', 'equipos', 'tecnicos'));
    }

    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('mantenimientos.index')->with('error', 'No tienes permisos para actualizar.');
        }
        
        $validated = $request->validate([
            'fecha_entrada' => 'required|date',
            'fecha_salida'  => 'nullable|date|after_or_equal:fecha_entrada',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string|max:500',
            'costo' => 'required|numeric|min:0|decimal:0,2',
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|integer|exists:equipos,id',
            'tecnico_id' => 'required|integer|exists:tecnicos,id',
        ]);

        try {
            DB::beginTransaction();



            $mantenimiento->update($validated);
            DB::commit();
            return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el mantenimiento. Intente nuevamente.')->withInput();
        }
    }

    public function factura(Mantenimiento $mantenimiento)
    {
        if (!$mantenimiento->fecha_salida) {
            return redirect()
                ->route('mantenimientos.index')
                ->with('error', 'No se puede generar la factura sin fecha de salida. Registre la salida de la orden e inténtelo de nuevo.');
        }

        $mantenimiento->load(['equipo.cliente', 'tecnico', 'user']);
        
        $pdf = Pdf::loadView('mantenimientos.factura', compact('mantenimiento'));
        // Tamaño POS 80mm = ~226.77 pt de ancho. Largo dinámico pero pondremos un valor alto o [0,0,226.77,600]
        $pdf->setPaper([0, 0, 226.77, 800], 'portrait');
        
        return $pdf->stream('factura_' . $mantenimiento->id_orden . '.pdf');
    }

    public function anular(Request $request, Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        $request->validate([
            'password_confirm' => 'required'
        ]);

        $adminPassword = \App\Models\User::where('role', 'admin')->value('password');
        if (!\Hash::check($request->password_confirm, Auth::user()->password) && 
            !\Hash::check($request->password_confirm, $adminPassword)) {
            return redirect()->back()->with('error', 'Contraseña incorrecta.');
        }

        try {
            DB::beginTransaction();
            
            $esAnulacion = !$mantenimiento->anulado;
            $mantenimiento->update(['anulado' => $esAnulacion]);

            if ($esAnulacion) {
                // Revertir stock
                foreach ($mantenimiento->stocks as $stock) {
                    \App\Models\Stock::where('id', $stock->id)->increment('cantidad', $stock->pivot->cantidad);
                }

                // Revertir abonos en Caja
                $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Mantenimiento')->first();
                if ($concepto && $mantenimiento->abonos->count() > 0) {
                    foreach ($mantenimiento->abonos as $abono) {
                        \App\Models\MovimientoCaja::where(function($query) use ($abono, $concepto, $mantenimiento) {
                            $query->where('abono_id', $abono->id)
                                  ->orWhere(function($sub) use ($abono, $concepto, $mantenimiento) {
                                      $sub->whereNull('abono_id')
                                          ->where('concepto_id', $concepto->id)
                                          ->where('monto', $abono->monto)
                                          ->where('fecha', $abono->fecha->toDateString())
                                          ->where('descripcion', 'like', "%Orden " . $mantenimiento->id_orden . "%");
                                  });
                        })
                        ->where('estado', 'activo')
                        ->update(['anulado' => true]);
                    }
                }
                $msg = 'Mantenimiento anulado correctamente. La transacción y stock asociados (si aplica) han sido revertidos.';
            } else {
                // Reactivar stock
                foreach ($mantenimiento->stocks as $stock) {
                    \App\Models\Stock::where('id', $stock->id)->decrement('cantidad', $stock->pivot->cantidad);
                }

                // Reactivar abonos en Caja
                $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Mantenimiento')->first();
                if ($concepto && $mantenimiento->abonos->count() > 0) {
                    foreach ($mantenimiento->abonos as $abono) {
                        \App\Models\MovimientoCaja::where(function($query) use ($abono, $concepto, $mantenimiento) {
                            $query->where('abono_id', $abono->id)
                                  ->orWhere(function($sub) use ($abono, $concepto, $mantenimiento) {
                                      $sub->whereNull('abono_id')
                                          ->where('concepto_id', $concepto->id)
                                          ->where('monto', $abono->monto)
                                          ->where('fecha', $abono->fecha->toDateString())
                                          ->where('descripcion', 'like', "%Orden " . $mantenimiento->id_orden . "%");
                                  });
                        })
                        ->update(['anulado' => false]);
                    }
                }
                $msg = 'Mantenimiento reactivado correctamente. El stock y caja han sido actualizados.';
            }

            DB::commit();
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando mantenimiento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al anular el mantenimiento.');
        }
    }
}
