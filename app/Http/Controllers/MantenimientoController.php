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

class MantenimientoController extends Controller
{
    /**
     * Muestra la lista de todos los mantenimientos registrados.
     * Carga las relaciones (equipo, cliente, técnico, usuario) para evitar múltiples consultas a la DB.
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user'])->orderBy('id', 'desc')->get();
        return view('mantenimientos.index', compact('mantenimientos'));
    }

    /**
     * Módulo de reportes con filtros dinámicos.
     * Permite filtrar por fechas, clientes, equipos, técnicos y estados.
     */
    public function reportes(Request $request)
    {
        $query = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user']);

        // Aplicación de filtros según los parámetros recibidos en el request
        if ($request->filled('fecha_desde')) $query->whereDate('fecha_entrada', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('fecha_entrada', '<=', $request->fecha_hasta);
        
        // Filtro complejo: Buscar mantenimientos de equipos que pertenecen a un cliente específico
        if ($request->filled('cliente_id') && $request->cliente_id !== 'todos') {
            $query->whereHas('equipo', function($q) use ($request) { $q->where('cliente_id', $request->cliente_id); });
        }
        
        if ($request->filled('equipo_id') && $request->equipo_id !== 'todos') $query->where('equipo_id', $request->equipo_id);
        if ($request->filled('tecnico_id') && $request->tecnico_id !== 'todos') $query->where('tecnico_id', $request->tecnico_id);
        if ($request->filled('user_id') && $request->user_id !== 'todos') $query->where('user_id', $request->user_id);
        if ($request->filled('tipo') && $request->tipo !== 'todos') $query->where('tipo', $request->tipo);
        if ($request->filled('reparacion') && $request->reparacion !== 'todos') $query->where('reparacion', $request->reparacion);

        $mantenimientos = $query->orderBy('id', 'desc')->get();

        // Lógica de exportación según el botón presionado
        if ($request->get('export') == 'excel') return Excel::download(new MantenimientosExport($mantenimientos), 'reporte.xlsx');
        if ($request->get('export') == 'pdf') return Pdf::loadView('mantenimientos.pdf', compact('mantenimientos'))->download('reporte.pdf');

        $clientes = Cliente::all();
        $equipos = Equipo::all();
        $tecnicos = Tecnico::all();
        $usuarios = User::all();

        return view('mantenimientos.reportes', compact('mantenimientos', 'clientes', 'equipos', 'tecnicos', 'usuarios'));
    }

    public function create()
    {
        $equipos = Equipo::all();
        $tecnicos = Tecnico::all();
        return view('mantenimientos.create', compact('equipos', 'tecnicos'));
    }

    /**
     * Guarda un nuevo mantenimiento en la base de datos.
     * Genera automáticamente el número de orden secuencial (ORD-X).
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrada' => 'required|date',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|exists:equipos,id',
            'tecnico_id' => 'required|exists:tecnicos,id',
        ]);

        // Cálculo del siguiente número de orden basado en el último ID
        $ultimo = Mantenimiento::orderByDesc('id')->first();
        $siguiente = $ultimo ? intval(preg_replace('/[^0-9]/', '', $ultimo->id_orden)) + 1 : 1;
        
        $data = $request->all();
        $data['id_orden'] = 'ORD-' . $siguiente;
        $data['user_id'] = Auth::id(); // Asigna el usuario que está logueado actualmente

        Mantenimiento::create($data);
        return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento registrado correctamente.');
    }

    public function edit(Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('mantenimientos.index')->with('error', 'Solo el administrador puede editar.');
        }
        $equipos = Equipo::all();
        $tecnicos = Tecnico::all();
        return view('mantenimientos.edit', compact('mantenimiento', 'equipos', 'tecnicos'));
    }

    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role !== 'admin') return redirect()->route('mantenimientos.index');
        
        $request->validate([
            'fecha_entrada' => 'required|date',
            'tipo' => 'required|in:preventivo,correctivo',
            'reparacion' => 'required|in:software,hardware',
            'descripcion' => 'required|string',
            'costo' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,terminado',
            'equipo_id' => 'required|exists:equipos,id',
            'tecnico_id' => 'required|exists:tecnicos,id',
        ]);

        $mantenimiento->update($request->all());
        return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento actualizado.');
    }

    public function destroy(Mantenimiento $mantenimiento)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('mantenimientos.index')->with('error', 'Solo el administrador puede eliminar.');
        }
        $mantenimiento->delete();
        return redirect()->route('mantenimientos.index')->with('success', 'Mantenimiento eliminado.');
    }
}
