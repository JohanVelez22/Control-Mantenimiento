<?php

namespace App\Http\Controllers;

use App\Exports\ElectronicasExport;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\AnulacionService;
use App\Services\OrdenEstadoService;
use App\Services\OrdenService;
use App\Services\PosTicketService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

        $query = Electronica::with(['equipo.cliente', 'equipo.proveedor', 'tecnico', 'user', 'abonos']);

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
                                ->orWhere('apellidos', 'like', "%{$s}%")
                                ->orWhere('identificacion', 'like', "%{$s}%");
                        })
                        ->orWhereHas('proveedor', fn ($q3) => $q3->where('nombre_razon_social', 'like', "%{$s}%"));
                })->orWhere('id_orden', 'like', "%{$s}%")
                    ->orWhere('descripcion_problema', 'like', "%{$s}%");
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
        $estados = app(OrdenEstadoService::class);
        $validated = $request->validate([
            'descripcion_problema' => 'required|string|max:1000',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'costo' => 'required|numeric|min:0|decimal:0,2|max:99999999.99',
            'estado' => 'required|in:pendiente,terminado',
        ] + OrdenEstadoService::reglasComunes(null) + OrdenEstadoService::reglas(), OrdenEstadoService::mensajes());

        [$validated, $eventos] = $estados->preparar($validated, null);

        if ($estados->requiereClaveAlCrear($validated)
            && ! app(AnulacionService::class)->adminPasswordValida((string) $request->input('admin_password'))) {
            return redirect()->back()->with('error', 'Para registrar una orden terminada con saldo pendiente, un técnico necesita la contraseña de un administrador.')->withInput();
        }

        try {
            DB::beginTransaction();

            // Número de orden atómico, siempre asignado por el servidor: el valor que
            // muestra el formulario es solo una vista previa y puede haber sido tomado
            // por otra orden creada al mismo tiempo.
            $validated['id_orden'] = app(OrdenService::class)
                ->siguiente('ELC-', Electronica::class, 'id_orden', null);

            $validated['user_id'] = auth()->id();

            $orden = Electronica::create($validated);
            $estados->registrarEventos($orden, $eventos);

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
        $electronica->load(['equipo.cliente', 'equipo.proveedor', 'tecnico', 'user', 'stocks', 'abonos.user', 'abonos.movimientoCaja']);
        $stocks_disponibles = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();
        $historial = auth()->user()->isAdmin() ? Evento::historialDeOrden($electronica) : null;
        $historialUrl = route('eventos.index', ['modelo_tipo' => Electronica::class, 'modelo_id' => $electronica->id]);

        return view('electronicas.show', compact('electronica', 'stocks_disponibles', 'historial', 'historialUrl'));
    }

    public function edit(Electronica $electronica)
    {
        if ($electronica->anulado) {
            return redirect()->route('electronicas.show', $electronica)->with('error', 'Por seguridad, no se puede editar una orden de electrónica que se encuentra anulada.');
        }

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
        if ($electronica->anulado) {
            return redirect()->route('electronicas.show', $electronica)->with('error', 'Por seguridad, no se puede editar una orden de electrónica que se encuentra anulada.');
        }

        // El técnico puede editar, pero debe confirmar con la contraseña de un admin.
        // El número de orden lo asigna el sistema y no se modifica (rompería las referencias
        // de caja y la consulta del cliente), igual que en Mantenimiento.
        $reglas = [
            'descripcion_problema' => 'required|string|max:1000',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'costo' => ['required', 'numeric', 'decimal:0,2', 'max:99999999.99', 'min:'.$electronica->costoMinimoPermitido()],
            'estado' => 'required|in:pendiente,terminado',
        ] + OrdenEstadoService::reglasComunes($electronica) + OrdenEstadoService::reglas();

        if (auth()->user()->isTecnico()) {
            $reglas['admin_password'] = 'required|string';
        }

        $validated = $request->validate($reglas, [
            'costo.min' => 'El costo no puede ser menor que lo ya abonado o que el valor de los repuestos asignados ($ '.number_format($electronica->costoMinimoPermitido(), 0, ',', '.').').',
        ] + OrdenEstadoService::mensajes());

        if (auth()->user()->isTecnico() &&
            ! app(AnulacionService::class)->adminPasswordValida($validated['admin_password'])) {
            return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para editar.')->withInput();
        }
        unset($validated['admin_password']);

        $estados = app(OrdenEstadoService::class);

        try {
            DB::beginTransaction();

            // Releer con bloqueo: las reglas de cierre dependen del estado y de lo abonado.
            $electronica = Electronica::whereKey($electronica->id)->lockForUpdate()->firstOrFail();
            [$validated, $eventos] = $estados->preparar($validated, $electronica);

            $electronica->update($validated);
            $estados->registrarEventos($electronica, $eventos);
            DB::commit();

            return redirect()->route('electronicas.index')
                ->with('success', 'Registro electrónico actualizado correctamente.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
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

            // Bloquear la fila y releer el estado: evita que dos peticiones simultáneas
            // reviertan el stock dos veces.
            $electronica = Electronica::whereKey($electronica->id)->lockForUpdate()->firstOrFail();
            $esAnulacion = ! $electronica->anulado;

            if ($error = app(AnulacionService::class)->verificarIntencion($request, $esAnulacion)) {
                DB::rollBack();

                return redirect()->back()->with('error', $error);
            }

            if ($esAnulacion && ($error = app(AnulacionService::class)->exigirMotivo($request))) {
                DB::rollBack();

                return redirect()->back()->with('error', $error)->withInput();
            }

            $electronica->update(['anulado' => $esAnulacion]);
            if ($esAnulacion) {
                app(AnulacionService::class)->registrarMotivo($electronica, $request, 'Orden '.$electronica->id_orden.' anulada');
            }

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

    /** S-04: comprobante de recepción (ticket POS) con el código de consulta del cliente. */
    public function recepcion(Electronica $electronica)
    {
        Gate::authorize('view', $electronica);
        abort_if($electronica->anulado, 404);
        $electronica->load(['equipo.cliente', 'equipo.proveedor', 'tecnico']);

        return PosTicketService::streamTicket('partials.recepcion_pos', [
            'orden' => $electronica,
            'descripcion' => $electronica->descripcion_problema,
        ], 'recepcion_'.$electronica->id_orden.'.pdf', 620);
    }

    public function factura(Electronica $electronica)
    {
        Gate::authorize('view', $electronica);
        if ($error = OrdenEstadoService::bloqueoFactura($electronica)) {
            return redirect()->back()->with('error', $error);
        }
        $electronica->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        $empresa = Configuracion::first();
        $formato = request('formato', $empresa->formato_factura ?? 'estandar');

        if ($formato === 'pos') {
            $stocksCount = $electronica->stocks ? $electronica->stocks->count() : 0;
            $abonosCount = $electronica->abonos ? $electronica->abonos->count() : 0;
            $obsLength = strlen($electronica->descripcion_problema ?? '');
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
        // B-10: las relaciones se cargan al construir la consulta, también para Excel y PDF
        // (antes eran unas 4 consultas por fila al exportar).
        $query = Electronica::with(['equipo.cliente', 'equipo.proveedor', 'tecnico', 'user']);

        // B-07: mismo rango por defecto que Mantenimiento (mes actual); sin él, exportar
        // volcaba toda la tabla.
        $fechaDesde = $request->input('fecha_desde') ?? $request->input('fecha_inicio') ?? date('Y-m-01');
        $fechaHasta = $request->input('fecha_hasta') ?? $request->input('fecha_fin') ?? date('Y-m-d');
        $request->merge(['fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]);

        // D-04: rango sobre la columna (usa el índice) en lugar de DATE(columna).
        $query->where('fecha_entrada', '>=', $fechaDesde)
            ->where('fecha_entrada', '<', Carbon::parse($fechaHasta)->addDay()->toDateString());
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
                            })
                            ->orWhereHas('proveedor', fn ($q3) => $q3->where('nombre_razon_social', 'like', "%{$search}%")
                                ->orWhere('identificacion', 'like', "%{$search}%"));
                    });
            });
        }

        // X-05/X-06: totales sobre toda la consulta filtrada (no solo la página) y sin las
        // anuladas, que se muestran aparte; igual en pantalla, PDF y Excel.
        $totales = [
            'cantidad' => (clone $query)->count(),
            'costo' => (float) (clone $query)->where('anulado', false)->sum('costo'),
            'anuladas' => (clone $query)->where('anulado', true)->count(),
            'costo_anuladas' => (float) (clone $query)->where('anulado', true)->sum('costo'),
        ];

        if ($limite = $this->limiteExportacion($totales['cantidad'], $request->get('export'))) {
            return $limite;
        }

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

        $registros = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
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
                    // Solo la identificación del cliente (el celular no es un dato secreto)
                    $sub->where('identificacion', $identificacion)
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(identificacion, ' ', ''), '-', ''), '.', '') = ?", [$cleanId]);
                })
                ->where(function ($q) use ($idOrden, $cleanOrden, $esNumero) {
                    $q->where('id_orden', $idOrden)
                        ->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(id_orden, ' ', ''), '-', ''), '.', '')) = ?", [$cleanOrden]);
                    if ($esNumero) {
                        // Un número solo se interpreta como número de orden, nunca como id interno
                        $q->orWhere('id_orden', 'ELC-'.$esNumero);
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
