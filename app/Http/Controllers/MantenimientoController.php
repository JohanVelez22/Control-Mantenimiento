<?php

namespace App\Http\Controllers;

use App\Exports\MantenimientosExport;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\AnulacionService;
use App\Services\OrdenEstadoService;
use App\Services\OrdenService;
use App\Services\PosTicketService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class MantenimientoController extends Controller
{
    /**
     * Muestra la lista de todos los mantenimientos registrados.
     * Carga las relaciones (equipo, cliente, técnico, usuario) para evitar múltiples consultas a la DB.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Mantenimiento::class);

        if ($request->has('locate')) {
            $id = $request->locate;
            // Calcular la página asumiendo orden descendente por ID
            $position = Mantenimiento::where('id', '>=', $id)->count();
            $page = ceil($position / 10) ?: 1;

            return redirect()->route('mantenimientos.index', ['page' => $page])->withFragment('mantenimiento-'.$id);
        }

        $query = Mantenimiento::with(['equipo.cliente', 'equipo.proveedor', 'tecnico', 'user', 'abonos']);

        // F-04: la búsqueda recorre todos los registros, no solo la página visible.
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('id_orden', 'like', "%{$s}%")
                    ->orWhere('descripcion', 'like', "%{$s}%")
                    ->orWhereHas('tecnico', fn ($q2) => $q2->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('equipo', function ($q2) use ($s) {
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
                    });
            });
        }

        $mantenimientos = $query->orderBy('id', 'desc')->paginate(10);

        return view('mantenimientos.index', compact('mantenimientos'));
    }

    public function show(Mantenimiento $mantenimiento)
    {
        Gate::authorize('view', $mantenimiento);

        $mantenimiento->load(['equipo.cliente', 'tecnico', 'user', 'abonos.user', 'abonos.movimientoCaja', 'stocks']);
        $stocks_disponibles = Stock::activos()->where('cantidad', '>', 0)->orderBy('producto')->get();

        return view('mantenimientos.show', compact('mantenimiento', 'stocks_disponibles'));
    }

    /** Duplicar un mantenimiento existente como nuevo borrador */
    public function duplicate(Mantenimiento $mantenimiento)
    {
        Gate::authorize('create', Mantenimiento::class);

        // Número de orden atómico para la copia (evita colisión con otra creación/duplicado).
        $siguiente = app(OrdenService::class)->siguiente('ORD-', Mantenimiento::class);

        // La copia no hereda la anulación ni los repuestos (la pivot no se replica):
        // su costo excluye el valor de los repuestos de la orden original.
        $nuevo = $mantenimiento->replicate(['anulado']);
        $nuevo->anulado = false;
        $nuevo->costo = max(0, (float) $mantenimiento->costo - $mantenimiento->valorRepuestos());
        $nuevo->id_orden = $siguiente;
        $nuevo->fecha_entrada = now()->toDateString();
        $nuevo->fecha_salida = null;
        $nuevo->estado = 'pendiente';
        $nuevo->user_id = Auth::id();
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

        // Sincroniza con el request para que Blade coincida
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
            $query->whereHas('equipo', function ($q) use ($request) {
                $q->where('cliente_id', $request->cliente_id);
            });
        }

        if ($request->filled('equipo_id') && $request->equipo_id !== 'todos') {
            $query->where('equipo_id', $request->equipo_id);
        }
        if ($request->filled('tecnico_id') && $request->tecnico_id !== 'todos') {
            $query->where('tecnico_id', $request->tecnico_id);
        }
        if ($request->filled('user_id') && $request->user_id !== 'todos') {
            $query->where('user_id', $request->user_id);
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
        if ($request->filled('min_cost')) {
            $query->where('costo', '>=', $request->min_cost);
        }
        if ($request->filled('max_cost')) {
            $query->where('costo', '<=', $request->max_cost);
        }

        // Búsqueda general
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
                                    ->orWhere('apellidos', 'like', "%{$search}%");
                            });
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

        // Lógica de exportación o paginación según el botón presionado
        if ($request->get('export') == 'excel') {
            $mantenimientos = $query->orderBy('id', 'desc')->get();

            return Excel::download(new MantenimientosExport($mantenimientos), 'Reporte_Mantenimientos_'.date('Y-m-d_His').'.xlsx');
        }
        if ($request->get('export') == 'pdf') {
            $mantenimientos = $query->orderBy('id', 'desc')->get();

            return Pdf::loadView('mantenimientos.pdf', compact('mantenimientos'))
                ->setPaper('a4', 'landscape')
                ->download('Reporte_Mantenimientos_'.date('Y-m-d_His').'.pdf');
        }

        $mantenimientos = $query->orderBy('id', 'desc')->paginate(10);

        $clientes = Cliente::orderBy('nombres')->orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'identificacion']);
        $equipos = Equipo::orderBy('nombre')->get(['id', 'nombre', 'modelo', 'serie']);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('mantenimientos.reportes', compact('mantenimientos', 'totales', 'clientes', 'equipos', 'tecnicos', 'usuarios'));
    }

    public function create()
    {
        $equipos = Equipo::with('cliente')->activos()->orderBy('nombre')->get();
        $tecnicos = Tecnico::activos()->orderBy('nombre')->get();

        // Consecutivo preview (sin bloqueo) para mostrar en el formulario.
        // El valor definitivo se recalcula con lockForUpdate en store().
        $nextOrden = app(OrdenService::class)
            ->siguiente('ORD-', Mantenimiento::class, 'id_orden', null, false);

        return view('mantenimientos.create', compact('equipos', 'tecnicos', 'nextOrden'));
    }

    /**
     * Guarda un nuevo mantenimiento en la base de datos.
     * Genera automáticamente el número de orden secuencial (ORD-X).
     */
    public function store(Request $request)
    {

        $estados = app(OrdenEstadoService::class);
        $validated = $request->validate([
            'fecha_entrada' => 'required|date',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string|max:500',
            'costo' => 'required|numeric|min:0|decimal:0,2|max:99999999.99',
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|integer|exists:equipos,id',
            'tecnico_id' => 'required|integer|exists:tecnicos,id',
        ] + OrdenEstadoService::reglas(), OrdenEstadoService::mensajes());

        [$validated, $eventos] = $estados->preparar($validated, null);

        if ($estados->requiereClaveAlCrear($validated)
            && ! app(AnulacionService::class)->adminPasswordValida((string) $request->input('admin_password'))) {
            return redirect()->back()->with('error', 'Para registrar una orden terminada con saldo pendiente, un técnico necesita la contraseña de un administrador.')->withInput();
        }

        try {
            DB::beginTransaction();

            // Número de orden atómico: OrdenService usa lockForUpdate dentro
            // de la transacción para eliminar la condición de carrera (race condition).
            $validated['id_orden'] = app(OrdenService::class)
                ->siguiente('ORD-', Mantenimiento::class);
            $validated['user_id'] = Auth::id();

            $orden = Mantenimiento::create($validated);
            $estados->registrarEventos($orden, $eventos);
            DB::commit();

            return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando mantenimiento: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al registrar el mantenimiento. Intente nuevamente.')->withInput();
        }
    }

    public function edit(Mantenimiento $mantenimiento)
    {
        if ($mantenimiento->anulado) {
            return redirect()->route('mantenimientos.show', $mantenimiento)->with('error', 'Por seguridad, no se puede editar una orden de mantenimiento que se encuentra anulada.');
        }

        $equipos = Equipo::where(function ($q) use ($mantenimiento) {
            $q->activos()->orWhere('id', $mantenimiento->equipo_id);
        })->with('cliente')->orderBy('nombre')->get();

        $tecnicos = Tecnico::where(function ($q) use ($mantenimiento) {
            $q->activos()->orWhere('id', $mantenimiento->tecnico_id);
        })->orderBy('nombre')->get();

        return view('mantenimientos.edit', compact('mantenimiento', 'equipos', 'tecnicos'));
    }

    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        if ($mantenimiento->anulado) {
            return redirect()->route('mantenimientos.show', $mantenimiento)->with('error', 'Por seguridad, no se puede editar una orden de mantenimiento que se encuentra anulada.');
        }

        // El técnico puede editar, pero debe confirmar con la contraseña de un admin.
        $reglas = [
            'fecha_entrada' => 'required|date',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string|max:500',
            'costo' => ['required', 'numeric', 'decimal:0,2', 'max:99999999.99', 'min:'.$mantenimiento->costoMinimoPermitido()],
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|integer|exists:equipos,id',
            'tecnico_id' => 'required|integer|exists:tecnicos,id',
        ] + OrdenEstadoService::reglas();

        if (Auth::user()->isTecnico()) {
            $reglas['admin_password'] = 'required';
        }

        $validated = $request->validate($reglas, [
            'costo.min' => 'El costo no puede ser menor que lo ya abonado o que el valor de los repuestos asignados ($ '.number_format($mantenimiento->costoMinimoPermitido(), 0, ',', '.').').',
        ] + OrdenEstadoService::mensajes());

        if (Auth::user()->isTecnico() &&
            ! app(AnulacionService::class)->adminPasswordValida($validated['admin_password'])) {
            return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador para editar.')->withInput();
        }
        unset($validated['admin_password']);

        $estados = app(OrdenEstadoService::class);

        try {
            DB::beginTransaction();

            // Releer con bloqueo: las reglas de cierre dependen del estado y de lo abonado.
            $mantenimiento = Mantenimiento::whereKey($mantenimiento->id)->lockForUpdate()->firstOrFail();
            [$validated, $eventos] = $estados->preparar($validated, $mantenimiento);

            $mantenimiento->update($validated);
            $estados->registrarEventos($mantenimiento, $eventos);
            DB::commit();

            return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento actualizado.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando mantenimiento: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al actualizar el mantenimiento. Intente nuevamente.')->withInput();
        }
    }

    public function factura(Mantenimiento $mantenimiento)
    {
        Gate::authorize('view', $mantenimiento);
        if ($error = OrdenEstadoService::bloqueoFactura($mantenimiento)) {
            return redirect()->back()->with('error', $error);
        }

        $mantenimiento->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        $empresa = Configuracion::first();
        $formato = request('formato', $empresa->formato_factura ?? 'estandar');

        if ($formato === 'pos') {
            $stocksCount = $mantenimiento->stocks ? $mantenimiento->stocks->count() : 0;
            $abonosCount = $mantenimiento->abonos ? $mantenimiento->abonos->count() : 0;
            $obsLength = strlen($mantenimiento->descripcion ?? '');
            $obsExtra = ceil($obsLength / 35) * 14;
            $fallbackHeight = max(700, 520 + $obsExtra + ($stocksCount * 36) + ($abonosCount * 28));

            return PosTicketService::streamTicket(
                'mantenimientos.factura_pos',
                compact('mantenimiento'),
                'ticket_mantenimiento_'.$mantenimiento->id_orden.'.pdf',
                $fallbackHeight
            );
        }

        $pdf = Pdf::loadView('mantenimientos.factura', compact('mantenimiento'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('factura_'.$mantenimiento->id_orden.'.pdf');
    }

    public function anular(Request $request, Mantenimiento $mantenimiento)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        try {
            DB::beginTransaction();

            // Bloquear la fila y releer el estado: evita que dos peticiones simultáneas
            // reviertan el stock dos veces.
            $mantenimiento = Mantenimiento::whereKey($mantenimiento->id)->lockForUpdate()->firstOrFail();
            $esAnulacion = ! $mantenimiento->anulado;

            if ($error = app(AnulacionService::class)->verificarIntencion($request, $esAnulacion)) {
                DB::rollBack();

                return redirect()->back()->with('error', $error);
            }

            if ($esAnulacion && ($error = app(AnulacionService::class)->exigirMotivo($request))) {
                DB::rollBack();

                return redirect()->back()->with('error', $error)->withInput();
            }

            $mantenimiento->update(['anulado' => $esAnulacion]);
            if ($esAnulacion) {
                app(AnulacionService::class)->registrarMotivo($mantenimiento, $request, 'Orden '.$mantenimiento->id_orden.' anulada');
            }

            // Reversión centralizada de stock y abonos en caja
            app(AnulacionService::class)
                ->revertirStockYAbonos($mantenimiento, $esAnulacion, 'Abono Mantenimiento', ['Orden']);

            $msg = $esAnulacion
                ? 'Mantenimiento anulado correctamente. La transacción y stock asociados (si aplica) han sido revertidos.'
                : 'Mantenimiento reactivado correctamente. El stock y caja han sido actualizados.';

            DB::commit();

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando mantenimiento: '.$e->getMessage());

            $errMsg = $e instanceof \DomainException ? $e->getMessage() : 'Error al anular el mantenimiento.';

            return redirect()->back()->with('error', $errMsg);
        }
    }

    /**
     * Consulta con doble factor para invitado: requiere cédula del cliente Y número de orden.
     * Protege la privacidad evitando enumeración o consulta cruzada entre clientes.
     */
    public function consulta(Request $request)
    {
        $identificacion = $request->get('identificacion') ?? $request->get('cedula');
        $idOrden = $request->get('id_orden') ?? $request->get('orden');
        $mantenimientos = collect();

        if ($identificacion && $idOrden) {
            $cleanId = preg_replace('/[\s\-\.]/', '', $identificacion);
            $cleanOrden = strtoupper(preg_replace('/[\s\-\.]/', '', $idOrden));
            $esNumero = is_numeric($idOrden) ? (int) $idOrden : null;
            if (! $esNumero && str_starts_with($cleanOrden, 'ORD')) {
                $esNumero = (int) preg_replace('/[^0-9]/', '', $idOrden);
            }

            $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico'])
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
                        $q->orWhere('id_orden', 'ORD-'.$esNumero);
                    }
                })
                ->latest()
                ->limit(10)
                ->get();

            if ($mantenimientos->isNotEmpty()) {
                $existentes = session('consultas_autorizadas', []);
                session(['consultas_autorizadas' => array_values(array_unique(array_merge($existentes, $mantenimientos->pluck('id')->all())))]);
            }
        } elseif ($request->has('identificacion') || $request->has('id_orden') || $request->has('q')) {
            return back()->with('error', 'Por motivos de seguridad y privacidad, debe ingresar tanto la cédula del cliente como el número de orden.');
        }

        return view('consulta.mantenimientos', compact('mantenimientos', 'identificacion', 'idOrden'));
    }
}
