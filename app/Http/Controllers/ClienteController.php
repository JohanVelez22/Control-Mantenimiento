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

        $validated = $request->validate([
            'nombre' => 'required|string|regex:/^[\pL\s\.\-]+$/u|max:80',
            'identificacion' => 'required|string|max:30|unique:clientes',
            'movil' => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:500'
        ]);

        Cliente::create($validated);

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

        $validated = $request->validate([
            'nombre' => 'required|string|regex:/^[\pL\s\.\-]+$/u|max:80',
            'identificacion' => 'required|string|max:30|unique:clientes,identificacion,' . $cliente->id,
            'movil' => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:500',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function anular(Cliente $cliente)
    {
        if (Auth::user()->role === 'invitado') {
            return redirect()->route('clientes.index')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $cliente->active = !$cliente->active;
        $cliente->save();

        $action = $cliente->active ? 'reactivado' : 'desactivado (anulado)';
        return redirect()->back()->with('success', "El cliente ha sido {$action} exitosamente.");
    }
}