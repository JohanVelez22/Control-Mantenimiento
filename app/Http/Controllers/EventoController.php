<?php

namespace App\Http\Controllers;

use App\Models\CategoriaStock;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden ver la auditoría de eventos.');
        }

        $query = Evento::with(['user'])->latest();

        // Filtros
        if ($request->filled('accion') && $request->accion !== 'todas') {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('user_id') && $request->user_id !== 'todos') {
            $query->where('user_id', $request->user_id);
        }

        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));

        $query->whereDate('created_at', '>=', $fechaDesde);
        $query->whereDate('created_at', '<=', $fechaHasta);

        if ($request->filled('search')) {
            $query->where('descripcion', 'LIKE', '%'.$request->search.'%')
                ->orWhere('modelo_tipo', 'LIKE', '%'.$request->search.'%');
        }

        $eventos = $query->paginate(30)->withQueryString();
        $users = User::orderBy('name')->get();
        $lookups = $this->getEntityLookups();

        return view('eventos.index', compact('eventos', 'users', 'lookups', 'fechaDesde', 'fechaHasta'));
    }

    public function show(Evento $evento)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden ver la auditoría de eventos.');
        }

        $evento->load(['user']);
        // Forzar parseo como array si fuera necesario
        $viejos = is_string($evento->valores_antiguos) ? json_decode($evento->valores_antiguos, true) : $evento->valores_antiguos;
        $nuevos = is_string($evento->valores_nuevos) ? json_decode($evento->valores_nuevos, true) : $evento->valores_nuevos;
        $lookups = $this->getEntityLookups();

        return view('eventos.show', compact('evento', 'viejos', 'nuevos', 'lookups'));
    }

    /**
     * Diccionario de resolución de llaves foráneas a nombres descriptivos
     */
    protected function getEntityLookups(): array
    {
        return [
            'users' => User::pluck('name', 'id')->toArray(),
            'clientes' => Cliente::get(['id', 'nombres', 'apellidos'])->mapWithKeys(function ($c) {
                return [$c->id => trim(($c->nombres ?? '').' '.($c->apellidos ?? ''))];
            })->filter(fn ($v) => ! empty($v))->toArray(),
            'proveedores' => Proveedor::pluck('nombre_razon_social', 'id')->toArray(),
            'tecnicos' => Tecnico::pluck('nombre', 'id')->toArray(),
            'categorias' => CategoriaStock::pluck('nombre', 'id')->toArray(),
            'conceptos' => ConceptoCaja::pluck('nombre', 'id')->toArray(),
            'stocks' => Stock::pluck('producto', 'id')->toArray(),
            'equipos' => Equipo::get(['id', 'nombre', 'marca', 'modelo'])->mapWithKeys(function ($e) {
                return [$e->id => trim(($e->nombre ?? '').' '.($e->marca ?? '').' '.($e->modelo ?? ''))];
            })->filter(fn ($v) => ! empty($v))->toArray(),
        ];
    }

    public function backup(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Solo administradores pueden generar copias de seguridad.');
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('app:backup-db');

            return redirect()->route('eventos.index')->with('success', 'Copia de seguridad del sistema generada exitosamente.');
        } catch (\Throwable $e) {
            return redirect()->route('eventos.index')->with('error', 'Error al generar la copia de seguridad: '.$e->getMessage());
        }
    }
}
