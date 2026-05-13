<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    // Mostrar lista de clientes (Accesible para Admin y Técnico)
    public function index()
    {
        $clientes = Cliente::orderBy('id', 'desc')->paginate(10);
        return view('clientes.index', compact('clientes'));
    }

    public function show(Cliente $cliente)
    {
        return redirect()->route('clientes.edit', $cliente);
    }

    // Mostrar formulario de creación (Accesible para Admin y Técnico)
    public function create()
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para crear.');
        }
        return view('clientes.create');
    }

    // Guardar nuevo cliente (Accesible para Admin y Técnico)
    public function store(Request $request)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para crear.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'required|string|max:50|unique:clientes',
            'movil' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string'
        ]);

        Cliente::create($request->all());

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    // Mostrar formulario de edición (SOLO ADMIN)
    public function edit(Cliente $cliente)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para editar.');
        }
        return view('clientes.edit', compact('cliente'));
    }

    // Actualizar cliente (SOLO ADMIN)
    public function update(Request $request, Cliente $cliente)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para actualizar.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'required|string|max:50|unique:clientes,identificacion,' . $cliente->id,
            'movil' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string'
        ]);

        $cliente->update($request->all());

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    // Eliminar cliente (SOLO ADMIN)
    public function destroy(Cliente $cliente)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para eliminar.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
