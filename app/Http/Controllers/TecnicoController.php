<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TecnicoController extends Controller
{
    public function index()
    {
        $tecnicos = Tecnico::orderBy('id', 'desc')->get();
        return view('tecnicos.index', compact('tecnicos'));
    }

    public function create()
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('tecnicos.index')->with('error', 'No tienes permisos para crear.');
        }
        return view('tecnicos.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('tecnicos.index')->with('error', 'No tienes permisos para crear.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'required|string|max:50|unique:tecnicos',
            'especialidad' => 'required|string|max:255',
            'movil' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string'
        ]);

        Tecnico::create($request->all());

        return redirect()->route('tecnicos.index')->with('success', 'Técnico registrado correctamente.');
    }

    public function edit(Tecnico $tecnico)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('tecnicos.index')->with('error', 'No tienes permisos para editar.');
        }
        
        return view('tecnicos.edit', compact('tecnico'));
    }

    public function update(Request $request, Tecnico $tecnico)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('tecnicos.index')->with('error', 'No tienes permisos para actualizar.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'required|string|max:50|unique:tecnicos,identificacion,' . $tecnico->id,
            'especialidad' => 'required|string|max:255',
            'movil' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string'
        ]);

        $tecnico->update($request->all());

        return redirect()->route('tecnicos.index')->with('success', 'Técnico actualizado correctamente.');
    }

    public function destroy(Tecnico $tecnico)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('tecnicos.index')->with('error', 'No tienes permisos para eliminar.');
        }

        $tecnico->delete();

        return redirect()->route('tecnicos.index')->with('success', 'Técnico eliminado correctamente.');
    }
}
