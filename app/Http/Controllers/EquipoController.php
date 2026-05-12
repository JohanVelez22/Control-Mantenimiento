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
        $equipos = Equipo::with(['cliente', 'user'])->orderBy('id', 'desc')->get();
        return view('equipos.index', compact('equipos'));
    }

    public function create()
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para crear.');
        }
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        return view('equipos.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para crear.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'serie' => 'required|string|max:255|unique:equipos',
            // Eliminado: validación de estado
            'cliente_id' => 'required|exists:clientes,id',
            'observacion' => 'nullable|string'
        ]);

        // Preparamos los datos y asignamos el usuario logueado automáticamente
        $data = $request->all();
        $data['user_id'] = Auth::id();

        Equipo::create($data);

        return redirect()->route('equipos.index')->with('success', 'Equipo registrado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para editar.');
        }
        
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        return view('equipos.edit', compact('equipo', 'clientes'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('equipos.index')->with('error', 'No tienes permisos para actualizar.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'serie' => 'required|string|max:255|unique:equipos,serie,' . $equipo->id,
            // Eliminado: validación de estado
            'cliente_id' => 'required|exists:clientes,id',
            'observacion' => 'nullable|string'
        ]);

        $equipo->update($request->all());

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
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
