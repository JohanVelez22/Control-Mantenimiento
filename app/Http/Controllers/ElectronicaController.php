<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Models\Tecnico;
use App\Models\Equipo;
use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ElectronicasExport;
use App\Services\OrdenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElectronicaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('locate')) {
            $id = $request->locate;
            // Calcular la página asumiendo orden descendente por ID
            $position = Electronica::where('id', '>=', $id)->count();
            $page = ceil($position / 10) ?: 1;
            return redirect()->route('electronicas.index', ['page' => $page])->withFragment('electronica-' . $id);
        }

        $query = Electronica::with('tecnico');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('equipo', function ($q2) use ($s) {
                    $q2->where('nombre', 'like', "%{$s}%")
                       ->orWhere('marca', 'like', "%{$s}%")
                       ->orWhere('modelo', 'like', "%{$s}%")
                       ->orWhere('serie', 'like', "%{$s}%")
                        ->orWhereHas('cliente', function ($q3) use ($s) {
                            $q3->where('nombres', 'like', "%{$s}%")
                               ->orWhere('apellidos', 'like', "%{$s}%");
                        });
                })->orWhere('id_orden', 'like', "%{$s}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $electronicas = $query->orderBy('id', 'desc')->paginate(10);

        return view('electronicas.index', compact('electronicas'));
    }

    public function create()
    {
        $tecnicos = Tecnico::orderBy('nombre')->get();
        $equipos = Equipo::with('cliente')->orderBy('nombre')->get();

        // Consecutivo preview (sin bloqueo) para mostrar en el formulario.
        // El valor definitivo se recalcula con lockForUpdate en store().
        $nextOrden = app(OrdenService::class)
            ->siguiente('ELC-', Electronica::class, 'id_orden', 4, false);

        return view('electronicas.create', compact('tecnicos', 'equipos', 'nextOrden'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_orden'             => 'nullable|string|unique:electronicas,id_orden',
            'equipo_id'            => 'required|exists:equipos,id',
            'descripcion_problema' => 'required|string',
            'tipo'                 => 'required|in:preventivo,correctivo',
            'costo'                => 'required|numeric|min:0',
            'estado'               => 'required|in:pendiente,terminado,anulado',
            'fecha_entrada'        => 'required|date',
            'fecha_salida'         => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id'           => 'required|exists:tecnicos,id',
        ]);

        try {
            DB::beginTransaction();

            // Número de orden atómico: OrdenService usa lockForUpdate dentro
            // de la transacción para eliminar la condición de carrera (race condition).
            if (empty($validated['id_orden'])) {
                $validated['id_orden'] = app(OrdenService::class)
                    ->siguiente('ELC-', Electronica::class, 'id_orden', 4);
            }

            $validated['user_id'] = auth()->id();

            Electronica::create($validated);

            DB::commit();

            return redirect()->route('electronicas.index')
                             ->with('success', "Registro electrónico {$validated['id_orden']} creado correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el equipo electrónico. Intente nuevamente.')->withInput();
        }
    }

    public function show(Electronica $electronica)
    {
        $electronica->load(['equipo.cliente', 'tecnico', 'user', 'stocks', 'abonos']);
        $stocks_disponibles = \App\Models\Stock::where('cantidad', '>', 0)->orderBy('producto')->get();
        return view('electronicas.show', compact('electronica', 'stocks_disponibles'));
    }

    public function edit(Electronica $electronica)
    {
        $tecnicos = Tecnico::orderBy('nombre')->get();
        $equipos = Equipo::with('cliente')->orderBy('nombre')->get();
        return view('electronicas.edit', compact('electronica', 'tecnicos', 'equipos'));
    }

    public function update(Request $request, Electronica $electronica)
    {
        // El técnico puede editar, pero debe confirmar con la contraseña de un admin.
        $reglas = [
            'id_orden'             => 'nullable|string|unique:electronicas,id_orden,' . $electronica->id,
            'equipo_id'            => 'required|exists:equipos,id',
            'descripcion_problema' => 'required|string',
            'tipo'                 => 'required|in:preventivo,correctivo',
            'costo'                => 'required|numeric|min:0',
            'estado'               => 'required|in:pendiente,terminado,anulado',
            'fecha_entrada'        => 'required|date',
            'fecha_salida'         => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id'           => 'required|exists:tecnicos,id',
        ];

        if (auth()->user()->isTecnico()) {
            $reglas['admin_password'] = 'required';
        }

        $validated = $request->validate($reglas);

        if (auth()->user()->isTecnico() &&
            !app(AnulacionService::class)->adminPasswordValida($validated['admin_password'])) {
            return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para editar.')->withInput();
        }

        try {
            DB::beginTransaction();

            $electronica->update($validated);
            DB::commit();

            return redirect()->route('electronicas.index')
                             ->with('success', 'Registro electrónico actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el registro electrónico.')->withInput();
        }
    }

    public function anular(Request $request, Electronica $electronica)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        // Técnico requiere contraseña de admin; admin usa su propia o la de admin.
        if (auth()->user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para anular.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.');
            }
        }

        try {
            DB::beginTransaction();

            $esAnulacion = !$electronica->anulado;
            $electronica->update(['anulado' => $esAnulacion]);

            // Reversión centralizada de stock y abonos en caja
            app(AnulacionService::class)
                ->revertirStockYAbonos($electronica, $esAnulacion, 'Abono Electrónica', ['ELC', 'Orden']);

            $msg = $esAnulacion
                ? 'Registro electrónico anulado correctamente.'
                : 'Registro electrónico reactivado correctamente.';

            DB::commit();
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando/reactivando electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar estado.');
        }
    }

    public function factura(Electronica $electronica)
    {
        $electronica->load(['equipo.cliente', 'tecnico', 'user']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('electronicas.factura', compact('electronica'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('factura_electronica_' . $electronica->id_orden . '.pdf');
    }

    public function reportes(Request $request)
    {
        $query = Electronica::query();

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_fin);
        }
        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('anulado') && $request->anulado !== 'todos') {
            if ($request->anulado === 'activo') {
                $query->where(function($q) {
                    $q->where('anulado', 0)->orWhereNull('anulado');
                });
            } elseif ($request->anulado === 'anulado') {
                $query->where('anulado', 1);
            }
        }
        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('tecnico_id') && $request->tecnico_id !== 'todos') {
            $query->where('tecnico_id', $request->tecnico_id);
        }
        if ($request->filled('user_id') && $request->user_id !== 'todos') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('equipo_id') && $request->equipo_id !== 'todos') {
            $query->where('equipo_id', $request->equipo_id);
        }
        if ($request->filled('cliente_id') && $request->cliente_id !== 'todos') {
            $query->whereHas('equipo', function($q) use ($request) {
                $q->where('cliente_id', $request->cliente_id);
            });
        }
        if ($request->filled('min_cost')) {
            $query->where('costo', '>=', $request->min_cost);
        }
        if ($request->filled('max_cost')) {
            $query->where('costo', '<=', $request->max_cost);
        }

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
                              $q3->where('nombres', 'like', "%{$search}%")
                                 ->orWhere('apellidos', 'like', "%{$search}%")
                                 ->orWhere('identificacion', 'like', "%{$search}%");
                          });
                  });
            });
        }

        $totales = [
            'cantidad' => (clone $query)->count(),
            'costo'    => (clone $query)->sum('costo'),
        ];

        if ($request->get('export') == 'excel') {
            $electronicas = $query->orderBy('id', 'desc')->get();
            return \Maatwebsite\Excel\Facades\Excel::download(new ElectronicasExport($electronicas), 'Reporte_Electronica_' . date('Y-m-d_His') . '.xlsx');
        }

        if ($request->get('export') == 'pdf') {
            $electronicas = $query->orderBy('id', 'desc')->get();
            return Pdf::loadView('electronicas.pdf', compact('electronicas'))
                ->setPaper('a4', 'landscape')
                ->download('Reporte_Electronica_' . date('Y-m-d_His') . '.pdf');
        }

        $registros = $query->with(['tecnico', 'user', 'equipo.cliente'])->orderBy('id', 'desc')->paginate(10);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);
        $clientes = Cliente::orderBy('nombres')->orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'identificacion']);
        $equipos  = Equipo::orderBy('nombre')->get(['id', 'nombre', 'modelo', 'serie']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('electronicas.reportes', compact('registros', 'totales', 'tecnicos', 'clientes', 'equipos', 'usuarios'));
    }

    /**
     * Consulta blindada para invitado: busca por cédula o teléfono del cliente.
     * No muestra lista completa. Requiere parámetro ?q= o muestra formulario vacío.
     */
    public function consulta(Request $request)
    {
        $query = $request->get('q');
        $electronicas = collect();

        if ($query) {
            // Validación estricta: alfanumérico, espacios, guiones, puntos, # (para órdenes como ELC-0001)
            if (!preg_match('/^[\d\s\-\.#]{5,30}$/', $query)) {
                return back()->with('error', 'Formato inválido. Use solo números, espacios, guiones, puntos o # (ej: 123456789, 3001234567, ELC-001).');
            }

            // Normalizar: quitar espacios/guiones/puntos para búsqueda
            $clean = preg_replace('/[\s\-\.]/', '', $query);

            $electronicas = Electronica::with(['equipo.cliente', 'tecnico'])
                ->where(function ($q) use ($clean) {
                    // 1. Por cédula/teléfono del cliente
                    $q->whereHas('equipo.cliente', function ($sub) use ($clean) {
                        $sub->where('identificacion', 'like', "%{$clean}%")
                          ->orWhere('telefono', 'like', "%{$clean}%")
                          ->orWhere('movil', 'like', "%{$clean}%");
                    })
                    // 2. Por número de orden (id_orden)
                    ->orWhere('id_orden', 'like', "%{$clean}%");
                })
                ->where('anulado', false)
                ->latest()
                ->limit(50)
                ->get();
        }

        return view('consulta.electronicas', compact('electronicas', 'query'));
    }
}
