<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\User;

class EventoController extends Controller
{
    public function index(Request $request)
    {
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
            $query->where('descripcion', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('modelo_tipo', 'LIKE', '%' . $request->search . '%');
        }

        $eventos = $query->paginate(30)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('eventos.index', compact('eventos', 'users', 'fechaDesde', 'fechaHasta'));
    }

    public function show(Evento $evento)
    {
        $evento->load(['user']);
        // Forzar parseo como array si fuera necesario
        $viejos = is_string($evento->valores_antiguos) ? json_decode($evento->valores_antiguos, true) : $evento->valores_antiguos;
        $nuevos = is_string($evento->valores_nuevos) ? json_decode($evento->valores_nuevos, true) : $evento->valores_nuevos;
        
        return view('eventos.show', compact('evento', 'viejos', 'nuevos'));
    }
}
