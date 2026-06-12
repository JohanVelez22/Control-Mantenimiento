<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Models\Tecnico;
use Carbon\Carbon;

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
        $query = Electronica::with('tecnico');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('cliente', 'like', "%{$s}%")
                  ->orWhere('dispositivo', 'like', "%{$s}%")
                  ->orWhere('id_orden', 'like', "%{$s}%");
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
        $nextOrden = self::nextOrdenId();
        return view('electronicas.create', compact('tecnicos', 'nextOrden'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_orden'             => 'nullable|string|unique:electronicas,id_orden',
            'cliente'              => 'required|string|max:255',
            'dispositivo'          => 'required|string|max:255',
            'marca'                => 'nullable|string|max:255',
            'descripcion_problema' => 'required|string',
            'tipo'                 => 'required|in:preventivo,correctivo',
            'costo'                => 'required|numeric|min:0',
            'estado'               => 'required|in:pendiente,terminado',
            'fecha_entrada'        => 'required|date',
            'fecha_salida'         => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id'           => 'required|exists:tecnicos,id',
        ]);

        if (empty($validated['id_orden'])) {
            $validated['id_orden'] = self::nextOrdenId();
        }

        $validated['user_id'] = auth()->id();

        Electronica::create($validated);

        return redirect()->route('electronicas.index')
                         ->with('success', "Registro electrónico {$validated['id_orden']} creado correctamente.");
    }

    public function edit(Electronica $electronica)
    {
        $tecnicos = Tecnico::orderBy('nombre')->get();
        return view('electronicas.edit', compact('electronica', 'tecnicos'));
    }

    public function update(Request $request, Electronica $electronica)
    {
        $validated = $request->validate([
            'id_orden'             => 'nullable|string|unique:electronicas,id_orden,' . $electronica->id,
            'cliente'              => 'required|string|max:255',
            'dispositivo'          => 'required|string|max:255',
            'marca'                => 'nullable|string|max:255',
            'descripcion_problema' => 'required|string',
            'tipo'                 => 'required|in:preventivo,correctivo',
            'costo'                => 'required|numeric|min:0',
            'estado'               => 'required|in:pendiente,terminado',
            'fecha_entrada'        => 'required|date',
            'fecha_salida'         => 'nullable|date|after_or_equal:fecha_entrada',
            'tecnico_id'           => 'required|exists:tecnicos,id',
        ]);

        $electronica->update($validated);

        return redirect()->route('electronicas.index')
                         ->with('success', 'Registro electrónico actualizado correctamente.');
    }

    public function destroy(Electronica $electronica)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('electronicas.index')->with('error', 'Solo el administrador puede eliminar.');
        }
        $electronica->delete();
        return redirect()->route('electronicas.index')
                         ->with('success', 'Registro electrónico eliminado.');
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
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('tecnico_id')) {
            $query->where('tecnico_id', $request->tecnico_id);
        }

        $totales = [
            'cantidad' => (clone $query)->count(),
            'costo'    => (clone $query)->sum('costo'),
        ];

        $registros = $query->with(['tecnico', 'user'])->orderBy('fecha_entrada', 'desc')->paginate(15);
        $tecnicos = Tecnico::orderBy('nombre')->get();

        return view('electronicas.reportes', compact('registros', 'totales', 'tecnicos'));
    }
}
