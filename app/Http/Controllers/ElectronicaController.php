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

class ElectronicaController extends Controller
{
    // Contador de orden, igual que en mantenimientos
    private static function nextOrdenId(): string
    {
        $last = Electronica::orderBy('id', 'desc')->first();
        $num = $last ? ((int) filter_var($last->id_orden, FILTER_SANITIZE_NUMBER_INT)) + 1 : 1;
        return 'ELC-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

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
        $nextOrden = self::nextOrdenId();
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
            \Illuminate\Support\Facades\DB::beginTransaction();

            if (empty($validated['id_orden'])) {
                $last = Electronica::lockForUpdate()->orderBy('id', 'desc')->first();
                $num = $last ? ((int) filter_var($last->id_orden, FILTER_SANITIZE_NUMBER_INT)) + 1 : 1;
                $validated['id_orden'] = 'ELC-' . str_pad($num, 4, '0', STR_PAD_LEFT);
            }

            $validated['user_id'] = auth()->id();

            Electronica::create($validated);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('electronicas.index')
                             ->with('success', "Registro electrónico {$validated['id_orden']} creado correctamente.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error registrando electrónica: ' . $e->getMessage());
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
        $validated = $request->validate([
            'id_orden'             => 'nullable|string|unique:electronicas,id_orden,' . $electronica->id,
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
            \Illuminate\Support\Facades\DB::beginTransaction();



            $electronica->update($validated);
            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('electronicas.index')
                             ->with('success', 'Registro electrónico actualizado correctamente.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error actualizando electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el registro electrónico.')->withInput();
        }
    }

    public function anular(Request $request, Electronica $electronica)
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para anular.');
        }

        $request->validate([
            'password_confirm' => 'required'
        ]);

        $adminPassword = \App\Models\User::where('role', 'admin')->value('password');
        if (!\Hash::check($request->password_confirm, auth()->user()->password) && 
            !\Hash::check($request->password_confirm, $adminPassword)) {
            return redirect()->back()->with('error', 'Contraseña incorrecta.');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $esAnulacion = !$electronica->anulado;
            $electronica->update(['anulado' => $esAnulacion]);

            if ($esAnulacion) {
                // Revertir stock
                foreach ($electronica->stocks as $stock) {
                    \App\Models\Stock::where('id', $stock->id)->increment('cantidad', $stock->pivot->cantidad);
                }

                // Revertir abonos en Caja
                $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Electrónica')->first();
                if ($concepto && $electronica->abonos->count() > 0) {
                    foreach ($electronica->abonos as $abono) {
                        \App\Models\MovimientoCaja::where(function($query) use ($abono, $concepto, $electronica) {
                            $query->where('abono_id', $abono->id)
                                  ->orWhere(function($sub) use ($abono, $concepto, $electronica) {
                                      $sub->whereNull('abono_id')
                                          ->where('concepto_id', $concepto->id)
                                          ->where('monto', $abono->monto)
                                          ->where('fecha', $abono->fecha->toDateString())
                                          ->where(function($descQuery) use ($electronica) {
                                              $descQuery->where('descripcion', 'like', "%ELC " . $electronica->id_orden . "%")
                                                        ->orWhere('descripcion', 'like', "%Orden " . $electronica->id_orden . "%");
                                          });
                                  });
                        })
                        ->where('estado', 'activo')
                        ->update(['anulado' => true]);
                    }
                }
                $msg = 'Registro electrónico anulado correctamente.';
            } else {
                // Reactivar stock
                foreach ($electronica->stocks as $stock) {
                    \App\Models\Stock::where('id', $stock->id)->decrement('cantidad', $stock->pivot->cantidad);
                }

                // Reactivar abonos en Caja
                $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Electrónica')->first();
                if ($concepto && $electronica->abonos->count() > 0) {
                    foreach ($electronica->abonos as $abono) {
                        \App\Models\MovimientoCaja::where(function($query) use ($abono, $concepto, $electronica) {
                            $query->where('abono_id', $abono->id)
                                  ->orWhere(function($sub) use ($abono, $concepto, $electronica) {
                                      $sub->whereNull('abono_id')
                                          ->where('concepto_id', $concepto->id)
                                          ->where('monto', $abono->monto)
                                          ->where('fecha', $abono->fecha->toDateString())
                                          ->where(function($descQuery) use ($electronica) {
                                              $descQuery->where('descripcion', 'like', "%ELC " . $electronica->id_orden . "%")
                                                        ->orWhere('descripcion', 'like', "%Orden " . $electronica->id_orden . "%");
                                          });
                                  });
                        })
                        ->update(['anulado' => false]);
                    }
                }
                $msg = 'Registro electrónico reactivado correctamente.';
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error anulando/reactivando electrónica: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar estado.');
        }
    }

    public function factura(Electronica $electronica)
    {
        return view('electronicas.factura', compact('electronica'));
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
                ->setPaper('a4', 'portrait')
                ->download('Reporte_Electronica_' . date('Y-m-d_His') . '.pdf');
        }

        $registros = $query->with(['tecnico', 'user', 'equipo.cliente'])->orderBy('id', 'desc')->paginate(10);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);
        $clientes = Cliente::orderBy('nombres')->orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'identificacion']);
        $equipos  = Equipo::orderBy('nombre')->get(['id', 'nombre', 'modelo', 'serie']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('electronicas.reportes', compact('registros', 'totales', 'tecnicos', 'clientes', 'equipos', 'usuarios'));
    }
}
