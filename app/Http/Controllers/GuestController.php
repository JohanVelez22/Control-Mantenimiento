<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Electronica;
use App\Models\Mantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GuestController extends Controller
{
    /**
     * Mostrar el panel dedicado para invitados
     *
     * @return View
     */
    public function dashboard()
    {
        $user = Auth::user();
        $cliente = Cliente::where('email', $user->email)->first();

        $mantenimientos = collect();
        $electronicas = collect();

        if ($cliente) {
            $mantenimientos = Mantenimiento::with(['equipo', 'tecnico', 'stocks'])
                ->whereHas('equipo', function ($q) use ($cliente) {
                    $q->where('cliente_id', $cliente->id);
                })
                ->where('anulado', false)
                ->orderBy('created_at', 'desc')
                ->get();

            $electronicas = Electronica::with(['equipo', 'tecnico', 'stocks'])
                ->whereHas('equipo', function ($q) use ($cliente) {
                    $q->where('cliente_id', $cliente->id);
                })
                ->where('anulado', false)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('guest.dashboard', compact('cliente', 'mantenimientos', 'electronicas'));
    }

    /**
     * Búsqueda segura en el panel de invitado con doble factor (identificación + orden).
     * Evita que terceros sin el número de orden o sin la cédula puedan acceder a datos de otros clientes.
     *
     * @return View
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:mantenimiento,electronica',
            'identificacion' => 'required|string|min:3|max:30',
            'id_orden' => 'required|string|min:1|max:30',
        ], [
            'identificacion.required' => 'La identificación (cédula o NIT) es obligatoria.',
            'identificacion.min' => 'La identificación debe tener al menos 3 caracteres.',
            'id_orden.required' => 'El número de orden es obligatorio.',
            'tipo.required' => 'El tipo de consulta es obligatorio.',
        ]);

        $tipo = $validated['tipo'];
        $identificacion = trim($validated['identificacion']);
        $cleanId = preg_replace('/[\s\-\.]/', '', $identificacion);

        $id_orden = trim($validated['id_orden']);
        $cleanOrden = strtoupper(preg_replace('/[\s\-\.]/', '', $id_orden));

        $es_numero = null;
        if (is_numeric($id_orden)) {
            $es_numero = (int) $id_orden;
        } else {
            if ($tipo === 'mantenimiento' && str_starts_with($cleanOrden, 'ORD')) {
                $es_numero = (int) preg_replace('/[^0-9]/', '', $id_orden);
            } elseif ($tipo === 'electronica' && (str_starts_with($cleanOrden, 'ELE') || str_starts_with($cleanOrden, 'ELC'))) {
                $es_numero = (int) preg_replace('/[^0-9]/', '', $id_orden);
            }
        }

        $mantenimientos = collect();
        $electronicas = collect();

        $clienteFilter = function ($sub) use ($identificacion, $cleanId) {
            $sub->where('identificacion', $identificacion)
                ->orWhere('telefono', $identificacion)
                ->orWhere('movil', $identificacion)
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(identificacion, ' ', ''), '-', ''), '.', '') = ?", [$cleanId]);
        };

        $ordenFilter = function ($q) use ($id_orden, $cleanOrden, $es_numero) {
            $q->where('id_orden', $id_orden)
                ->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(id_orden, ' ', ''), '-', ''), '.', '')) = ?", [$cleanOrden]);
            if ($es_numero) {
                $q->orWhere('id', $es_numero);
            }
        };

        if ($tipo === 'mantenimiento') {
            $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico', 'stocks'])
                ->where('anulado', false)
                ->whereHas('equipo.cliente', $clienteFilter)
                ->where($ordenFilter)
                ->get();

            if ($mantenimientos->isNotEmpty()) {
                $existentes = session('consultas_autorizadas', []);
                session(['consultas_autorizadas' => array_values(array_unique(array_merge($existentes, $mantenimientos->pluck('id')->all())))]);
            }
        } else {
            $electronicas = Electronica::with(['equipo.cliente', 'tecnico', 'stocks'])
                ->where('anulado', false)
                ->whereHas('equipo.cliente', $clienteFilter)
                ->where($ordenFilter)
                ->get();

            if ($electronicas->isNotEmpty()) {
                $existentes = session('consultas_autorizadas_elec', []);
                session(['consultas_autorizadas_elec' => array_values(array_unique(array_merge($existentes, $electronicas->pluck('id')->all())))]);
            }
        }

        $cliente = null;
        $searched = true;

        return view('guest.dashboard', compact('cliente', 'mantenimientos', 'electronicas', 'searched', 'identificacion', 'id_orden', 'tipo'));
    }
}
