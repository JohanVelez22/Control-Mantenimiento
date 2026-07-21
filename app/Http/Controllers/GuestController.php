<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Models\Cliente;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use Illuminate\Support\Facades\Auth;

class GuestController extends Controller
{
    /**
     * Mostrar el panel dedicado para invitados
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();
        $cliente = Cliente::where('email', $user->email)->first();
        
        $mantenimientos = collect();
        $electronicas = collect();

        if ($cliente) {
            $mantenimientos = Mantenimiento::with(['equipo', 'tecnico'])
                ->whereHas('equipo', function($q) use ($cliente) {
                    $q->where('cliente_id', $cliente->id);
                })
                ->where('anulado', false)
                ->orderBy('created_at', 'desc')
                ->get();

            $electronicas = Electronica::with(['equipo', 'tecnico'])
                ->whereHas('equipo', function($q) use ($cliente) {
                    $q->where('cliente_id', $cliente->id);
                })
                ->where('anulado', false)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('guest.dashboard', compact('cliente', 'mantenimientos', 'electronicas'));
    }

/**
     * Redirigir búsquedas desde el panel de invitado a las consultas específicas
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:mantenimiento,electronica',
            'query' => 'required|string|min:3|max:30',
        ]);

        $query = trim($validated['query']);
        $tipo = $validated['tipo'];
        
        $mantenimientos = collect();
        $electronicas = collect();

        // Extraer numero si viene con formato, pero validando que corresponda al tipo correcto
        // para evitar que "ELC-1" encuentre la orden "ORD-1" de mantenimiento.
        $es_numero = null;
        if (is_numeric($query)) {
            $es_numero = (int)$query;
        } else {
            $upperQuery = strtoupper($query);
            if ($tipo === 'mantenimiento' && str_starts_with($upperQuery, 'ORD')) {
                $es_numero = (int) preg_replace('/[^0-9]/', '', $query);
            } elseif ($tipo === 'electronica' && (str_starts_with($upperQuery, 'ELE') || str_starts_with($upperQuery, 'ELC'))) {
                $es_numero = (int) preg_replace('/[^0-9]/', '', $query);
            }
        }

        if ($tipo === 'mantenimiento') {
            $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico'])
                ->where('anulado', false)
                ->where(function($q) use ($query, $es_numero) {
                    $q->where('id_orden', 'LIKE', "%{$query}%");
                    if ($es_numero) {
                        $q->orWhere('id', $es_numero);
                    }
                    $q->orWhereHas('equipo.cliente', function($sub) use ($query) {
                        $sub->where('identificacion', 'LIKE', "%{$query}%");
                    });
                })->get();
        } else {
            $electronicas = Electronica::with(['equipo.cliente', 'tecnico'])
                ->where('anulado', false)
                ->where(function($q) use ($query, $es_numero) {
                    $q->where('id_orden', 'LIKE', "%{$query}%");
                    if ($es_numero) {
                        $q->orWhere('id', $es_numero);
                    }
                    $q->orWhereHas('equipo.cliente', function($sub) use ($query) {
                        $sub->where('identificacion', 'LIKE', "%{$query}%");
                    });
                })->get();
        }

        $cliente = null;
        $searched = true;

        return view('guest.dashboard', compact('cliente', 'mantenimientos', 'electronicas', 'searched', 'query', 'tipo'));
    }
}