<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoController extends Controller
{
    public function index()
    {
        // Cargamos 'cliente' y 'user' para mostrar quién registró el equipo
        $equipos = Equipo::with(['cliente', 'user'])->orderBy('id', 'desc')->paginate(10);
        return view('equipos.index', compact('equipos'));
    }

    public function show(Equipo $equipo)
    {
        return redirect()->route('equipos.edit', $equipo);
    }

    public function create()
    {
        $clientes = Cliente::activos()->orderBy('nombres')->orderBy('apellidos')->get();
        return view('equipos.create', compact('clientes'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'nombre' => 'required|string|max:80',
            'marca' => 'required|string|max:80',
            'modelo' => 'required|string|max:80',
            'serie' => 'required|string|max:80|unique:equipos',
            'cliente_id' => 'required|integer|exists:clientes,id',
            'observacion' => 'nullable|string|max:500'
        ]);

        $validated['user_id'] = Auth::id();

        Equipo::create($validated);

        return redirect()->route('equipos.index')->with('success', 'Equipo registrado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        
        $clientes = Cliente::where(function($q) use ($equipo) {
            $q->activos()->orWhere('id', $equipo->cliente_id);
        })->orderBy('nombres')->orderBy('apellidos')->get();
        return view('equipos.edit', compact('equipo', 'clientes'));
    }

    public function update(Request $request, Equipo $equipo)
    {

        $validated = $request->validate([
            'nombre' => 'required|string|max:80',
            'marca' => 'required|string|max:80',
            'modelo' => 'required|string|max:80',
            'serie' => 'required|string|max:80|unique:equipos,serie,' . $equipo->id,
            'cliente_id' => 'required|integer|exists:clientes,id',
            'observacion' => 'nullable|string|max:500',
        ]);

        $equipo->update($validated);

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function anular(Equipo $equipo)
    {

        $equipo->active = !$equipo->active;
        $equipo->save();

        $action = $equipo->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El equipo ha sido {$action} exitosamente.");
    }

    public function destroy(Equipo $equipo)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para eliminar.');
        }

        $equipo->delete();

        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado correctamente.');
    }
}
