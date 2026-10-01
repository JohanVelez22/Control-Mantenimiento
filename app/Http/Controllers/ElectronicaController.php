<?php

namespace App\Http\Controllers;

use App\Exports\ElectronicasExport;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\AnulacionService;
use App\Services\OrdenService;
use App\Services\PosTicketService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ElectronicaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Electronica::class);

        if ($request->has('locate')) {
            $id = $request->locate;
            // Calcular la página asumiendo orden descendente por ID
            $position = Electronica::where('id', '>=', $id)->count();
            $page = ceil($position / 10) ?: 1;

            return redirect()->route('electronicas.index', ['page' => $page])->withFragment('electronica-'.$id);
        }

        $query = Electronica::with(['equipo.cliente', 'tecnico', 'user', 'abonos']);

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
        $tecnicos = Tecnico::activos()->orderBy('nombre')->get();
        $equipos = Equipo::with('cliente')->activos()->orderBy('nombre')->get();

        // Consecutivo preview (sin bloqueo) para mostrar en el formulario.
        // El valor definitivo se recalcula con lockForUpdate en store().
        $nextOrden = app(OrdenService::class)
            ->siguiente('ELC-', Electronica::class, 'id_orden', null, false);

        return view('electronicas.create', compact('tecnicos', 'equipos', 'nextOrden'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_orden' => 'nullable|string|unique:electronicas,id_orden',
            'equipo_id' => 'required|exists:equipos,id',
            'descripcion_problema' => 'required|string',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,terminado',
            'fecha_entrada' => 'required|date',
            'fecha_salida' => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id' => 'required|exists:tecnicos,id',
        ]);

        try {
            DB::beginTransaction();

            // Número de orden atómico: OrdenService usa lockForUpdate dentro
            // de la transacción para eliminar la condición de carrera (race condition).
            if (empty($validated['id_orden'])) {
                $validated['id_orden'] = app(OrdenService::class)
                    ->siguiente('ELC-', Electronica::class, 'id_orden', null);
            }

            $validated['user_id'] = auth()->id();

            Electronica::create($validated);

            DB::commit();

            return redirect()->route('electronicas.index')
                ->with('success', "Registro electrónico {$validated['id_orden']} creado correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando electrónica: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al registrar el equipo electrónico. Intente nuevamente.')->withInput();
        }
    }

    public function show(Electronica $electronica)
    {
        Gate::authorize('view', $electronica);
        $electronica->load(['equipo.cliente', 'tecnico', 'user', 'stocks', 'abonos.user', 'abonos.movimientoCaja']);
        $stocks_disponibles = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();

        return view('electronicas.show', compact('electronica', 'stocks_disponibles'));
    }

    public function edit(Electronica $electronica)
    {
        $tecnicos = Tecnico::where(function ($q) use ($electronica) {
            $q->activos()->orWhere('id', $electronica->tecnico_id);
        })->orderBy('nombre')->get();
        $equipos = Equipo::where(function ($q) use ($electronica) {
            $q->activos()->orWhere('id', $electronica->equipo_id);
        })->with('cliente')->orderBy('nombre')->get();

        return view('electronicas.edit', compact('electronica', 'tecnicos', 'equipos'));
    }

    public function update(Request $request, Electronica $electronica)
    {
        // El técnico puede editar, pero debe confirmar con la contraseña de un admin.
        $reglas = [
            'id_orden' => 'nullable|string|unique:electronicas,id_orden,'.$electronica->id,
            'equipo_id' => 'required|exists:equipos,id',
            'descripcion_problema' => 'required|string',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,terminado',
            'fecha_entrada' => 'required|date',
            'fecha_salida' => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id' => 'required|exists:tecnicos,id',
        ];

        if (auth()->user()->isTecnico()) {
            $reglas['admin_password'] = 'required';
        }

        $validated = $request->validate($reglas);

        if (auth()->user()->isTecnico() &&
            ! app(AnulacionService::class)->adminPasswordValida($validated['admin_password'])) {
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
            Log::error('Error actualizando electrónica: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al actualizar el registro electrónico.')->withInput();
        }
    }

    public function anular(Request $request, Electronica $electronica)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        try {
            DB::beginTransaction();

            $esAnulacion = ! $electronica->anulado;
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
            Log::error('Error anulando/reactivando electrónica: '.$e->getMessage());

            $errMsg = $e instanceof \DomainException ? $e->getMessage() : 'Error al cambiar estado.';

            return redirect()->back()->with('error', $errMsg);
        }
    }

    public function factura(Electronica $electronica)
    {
        Gate::authorize('view', $electronica);
        if (! $electronica->fecha_salida) {
            return redirect()
                ->route('electronicas.index')
                ->with('error', 'No se puede generar la factura sin fecha de salida. Registre la salida de la orden e inténtelo de nuevo.');
        }
        $electronica->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        $empresa = Configuracion::first();
        $formato = request('formato', $empresa->formato_factura ?? 'estandar');

        if ($formato === 'pos') {
            $stocksCount = $electronica->stocks ? $electronica->stocks->count() : 0;
            $abonosCount = $electronica->abonos ? $electronica->abonos->count() : 0;
            $obsLength = strlen($electronica->descripcion ?? '');
            $obsExtra = ceil($obsLength / 35) * 14;
            $fallbackHeight = max(700, 520 + $obsExtra + ($stocksCount * 36) + ($abonosCount * 28));

            return PosTicketService::streamTicket(
                'electronicas.factura_pos',
                compact('electronica'),
                'ticket_electronica_'.$electronica->id_orden.'.pdf',
                $fallbackHeight
            );
        }

        $pdf = Pdf::loadView('electronicas.factura', compact('electronica'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('factura_electronica_'.$electronica->id_orden.'.pdf');
    }

    public function reportes(Request $request)
    {
        $query = Electronica::query();

        $fechaDesde = $request->input('fecha_desde') ?? $request->input('fecha_inicio');
        $fechaHasta = $request->input('fecha_hasta') ?? $request->input('fecha_fin');

        if ($fechaDesde) {
            $query->whereDate('fecha_entrada', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha_entrada', '<=', $fechaHasta);
        }
        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('anulado') && $request->anulado !== 'todos') {
            if ($request->anulado === 'activo') {
                $query->where(function ($q) {
                    $q->where('anulado', 0)->orWhereNull('anulado');
                });
            } elseif ($request->anulado === 'anulado') {
                $query->where('anulado', 1);
            }
        }
        if ($request->filled('tipo_rep') && $request->tipo_rep !== 'todos') {
            $val = $request->tipo_rep;
            if (in_array($val, ['preventivo', 'correctivo'])) {
                $query->where('tipo', $val);
            } elseif (in_array($val, ['software', 'hardware'])) {
                $query->where('reparacion', $val);
            }
        } else {
            if ($request->filled('tipo') && $request->tipo !== 'todos') {
                $query->where('tipo', $request->tipo);
            }
            if ($request->filled('reparacion') && $request->reparacion !== 'todos') {
                $query->where('reparacion', $request->reparacion);
            }
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
            $query->whereHas('equipo', function ($q) use ($request) {
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
            $query->where(function ($q) use ($search) {
                $q->where('id_orden', 'like', "%{$search}%")
                    ->orWhereHas('equipo', function ($q2) use ($search) {
                        $q2->where('nombre', 'like', "%{$search}%")
                            ->orWhere('marca', 'like', "%{$search}%")
                            ->orWhere('modelo', 'like', "%{$search}%")
                            ->orWhere('serie', 'like', "%{$search}%")
                            ->orWhereHas('cliente', function ($q3) use ($search) {
                                $q3->where('nombres', 'like', "%{$search}%")
                                    ->orWhere('apellidos', 'like', "%{$search}%")
                                    ->orWhere('identificacion', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $totales = [
            'cantidad' => (clone $query)->count(),
            'costo' => (clone $query)->sum('costo'),
        ];

        if ($request->get('export') == 'excel') {
            $electronicas = $query->orderBy('id', 'desc')->get();

            return Excel::download(new ElectronicasExport($electronicas), 'Reporte_Electronica_'.date('Y-m-d_His').'.xlsx');
        }

        if ($request->get('export') == 'pdf') {
            $electronicas = $query->orderBy('id', 'desc')->get();

            return Pdf::loadView('electronicas.pdf', compact('electronicas'))
                ->setPaper('a4', 'landscape')
                ->download('Reporte_Electronica_'.date('Y-m-d_His').'.pdf');
        }

        $registros = $query->with(['tecnico', 'user', 'equipo.cliente'])->orderBy('id', 'desc')->paginate(10);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);
        $clientes = Cliente::orderBy('nombres')->orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'identificacion']);
        $equipos = Equipo::orderBy('nombre')->get(['id', 'nombre', 'modelo', 'serie']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('electronicas.reportes', compact('registros', 'totales', 'tecnicos', 'clientes', 'equipos', 'usuarios'));
    }

    /**
     * Consulta con doble factor para invitado: requiere cédula del cliente Y número de orden.
     * Protege la privacidad evitando enumeración o consulta cruzada entre clientes.
     */
    public function consulta(Request $request)
    {
        $identificacion = $request->get('identificacion') ?? $request->get('cedula');
        $idOrden = $request->get('id_orden') ?? $request->get('orden');
        $electronicas = collect();

        if ($identificacion && $idOrden) {
            $cleanId = preg_replace('/[\s\-\.]/', '', $identificacion);
            $cleanOrden = strtoupper(preg_replace('/[\s\-\.]/', '', $idOrden));
            $esNumero = is_numeric($idOrden) ? (int) $idOrden : null;
            if (! $esNumero && (str_starts_with($cleanOrden, 'ELE') || str_starts_with($cleanOrden, 'ELC'))) {
                $esNumero = (int) preg_replace('/[^0-9]/', '', $idOrden);
            }

            $electronicas = Electronica::with(['equipo.cliente', 'tecnico'])
                ->where('anulado', false)
                ->whereHas('equipo.cliente', function ($sub) use ($identificacion, $cleanId) {
                    $sub->where('identificacion', $identificacion)
                        ->orWhere('telefono', $identificacion)
                        ->orWhere('movil', $identificacion)
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(identificacion, ' ', ''), '-', ''), '.', '') = ?", [$cleanId]);
                })
                ->where(function ($q) use ($idOrden, $cleanOrden, $esNumero) {
                    $q->where('id_orden', $idOrden)
                        ->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(id_orden, ' ', ''), '-', ''), '.', '')) = ?", [$cleanOrden]);
                    if ($esNumero) {
                        $q->orWhere('id', $esNumero);
                    }
                })
                ->latest()
                ->limit(10)
                ->get();

            if ($electronicas->isNotEmpty()) {
                $existentes = session('consultas_autorizadas_elec', []);
                session(['consultas_autorizadas_elec' => array_values(array_unique(array_merge($existentes, $electronicas->pluck('id')->all())))]);
            }
        } elseif ($request->has('identificacion') || $request->has('id_orden') || $request->has('q')) {
            return back()->with('error', 'Por motivos de seguridad y privacidad, debe ingresar tanto la cédula del cliente como el número de orden.');
        }

        return view('consulta.electronicas', compact('electronicas', 'identificacion', 'idOrden'));
    }
}
