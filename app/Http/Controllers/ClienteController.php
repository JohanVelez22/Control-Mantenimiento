<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Helpers\ColombiaHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('nombres', 'like', "%{$s}%")
                  ->orWhere('apellidos', 'like', "%{$s}%")
                  ->orWhere('identificacion', 'like', "%{$s}%")
                  ->orWhere('movil', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tipo_cliente')) {
            $query->where('tipo_cliente', $request->tipo_cliente);
        }

        $clientes = $query->orderBy('id', 'desc')->paginate(15);
        return view('clientes.index', compact('clientes'));
    }

    public function show(Cliente $cliente)
    {
        return redirect()->route('clientes.edit', $cliente);
    }

    public function create()
    {
        $departamentos = ColombiaHelper::departamentos();
        $tiposId       = ColombiaHelper::tiposIdentificacion();
        return view('clientes.create', compact('departamentos', 'tiposId'));
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'nombres'            => 'required|string|max:60',
            'apellidos'          => 'required|string|max:80',
            'tipo_identificacion'=> 'required|in:cedula_ciudadania,cedula_extranjeria,nit,pasaporte,tarjeta_identidad,rut',
            'identificacion'     => 'required|string|max:30|unique:clientes',
            'genero'             => 'required|in:masculino,femenino,indefinido',
            'tipo_cliente'       => 'required|in:cliente,tecnico',
            'movil'              => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email'              => 'nullable|email|max:100',
            'direccion'          => 'nullable|string|max:500',
            'departamento'       => 'nullable|string|max:60',
            'municipio'          => 'nullable|string|max:80',
        ]);

        Cliente::create($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        $departamentos = ColombiaHelper::departamentos();
        $tiposId       = ColombiaHelper::tiposIdentificacion();
        $municipios    = $cliente->departamento
            ? ColombiaHelper::municipiosDe($cliente->departamento)
            : [];
        return view('clientes.edit', compact('cliente', 'departamentos', 'tiposId', 'municipios'));
    }

    public function update(Request $request, Cliente $cliente)
    {

        $validated = $request->validate([
            'nombres'            => 'required|string|max:60',
            'apellidos'          => 'required|string|max:80',
            'tipo_identificacion'=> 'required|in:cedula_ciudadania,cedula_extranjeria,nit,pasaporte,tarjeta_identidad,rut',
            'identificacion'     => 'required|string|max:30|unique:clientes,identificacion,' . $cliente->id,
            'genero'             => 'required|in:masculino,femenino,indefinido',
            'tipo_cliente'       => 'required|in:cliente,tecnico',
            'movil'              => 'required|string|regex:/^[\d\+\-\s\(\)]+$/|max:30',
            'email'              => 'nullable|email|max:100',
            'direccion'          => 'nullable|string|max:500',
            'departamento'       => 'nullable|string|max:60',
            'municipio'          => 'nullable|string|max:80',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function anular(\Illuminate\Http\Request $request, Cliente $cliente)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role === 'invitado') {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $password = $request->input('admin_password') ?? $request->input('password_confirm');
        $request->merge(['admin_password' => $password, 'password_confirm' => $password]);

        if (\Illuminate\Support\Facades\Auth::user()->isTecnico()) {
            $request->validate(['admin_password' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->adminPasswordValida($request->admin_password)) {
                return redirect()->back()->with('error', 'Se requiere la contraseña de un administrador.')->withInput();
            }
        } else {
            $request->validate(['password_confirm' => 'required']);
            if (!app(\App\Services\AnulacionService::class)->passwordValida($request->password_confirm)) {
                return redirect()->back()->with('error', 'Contraseña incorrecta.');
            }
        }

        $cliente->active = !$cliente->active;
        $cliente->save();

        $action = $cliente->active ? 'reactivado' : 'desactivado';
        return redirect()->back()->with('success', "El cliente ha sido {$action} exitosamente.");
    }

    /**
     * AJAX: retorna municipios de un departamento dado.
     */
    public function municipios(Request $request)
    {
        $dep = $request->get('departamento', '');
        return response()->json(ColombiaHelper::municipiosDe($dep));
    }
}