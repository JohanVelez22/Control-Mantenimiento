<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Helpers\ColombiaHelper;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->has('locate')) {
            $id       = $request->locate;
            $position = Proveedor::where('id', '>=', $id)->count();
            $page     = ceil($position / 10) ?: 1;
            return redirect()->route('proveedores.index', ['page' => $page])
                             ->withFragment('proveedor-' . $id);
        }

        $query = Proveedor::query()->activos();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nombre_razon_social', 'like', "%{$s}%")
                  ->orWhere('identificacion', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('departamento', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_entidad', $request->tipo);
        }

        $proveedores = $query->orderBy('id', 'desc')->paginate(10);

        return view('proveedores.index', compact('proveedores'));
    }

    public function create(): View
    {
        $departamentos = ColombiaHelper::departamentos();
        $tiposId       = ColombiaHelper::tiposIdentificacion();
        return view('proveedores.create', compact('departamentos', 'tiposId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_entidad'        => ['required', Rule::in(['persona', 'empresa'])],
            'tipo_identificacion' => ['required', Rule::in(array_keys(ColombiaHelper::tiposIdentificacion()))],
            'identificacion'      => ['required', 'string', 'max:30', 'unique:proveedores,identificacion'],
            'nombre_razon_social' => ['required', 'string', 'max:80'],
            'telefono'            => ['nullable', 'string', 'max:30'],
            'telefono2'           => ['nullable', 'string', 'max:30'],
            'contacto_nombre'     => ['nullable', 'string', 'max:80'],
            'email'               => ['nullable', 'email', 'max:100'],
            'direccion'           => ['nullable', 'string', 'max:500'],
            'departamento'        => ['nullable', 'string', 'max:60'],
            'municipio'           => ['nullable', 'string', 'max:80'],
            'notas'               => ['nullable', 'string', 'max:500'],
        ]);

        try {
            Proveedor::create($validated);
            return redirect()->route('proveedores.index')
                ->with('success', 'Proveedor registrado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error creando proveedor: ' . $e->getMessage());
            return back()->with('error', 'Error al guardar el proveedor.')->withInput();
        }
    }

    public function show(Proveedor $proveedor): View
    {
        $proveedor->load(['stocks', 'facturas.items.stock', 'facturas.user']);
        $comprasTotales = $proveedor->facturas()->where('tipo_movimiento', 'compra')->sum('total_documento');
        $comprasPagadas = $proveedor->facturas()->where('tipo_movimiento', 'compra')->sum('total_pagado');
        $saldoProveedor = $comprasTotales - $comprasPagadas;

        return view('proveedores.show', compact('proveedor', 'comprasTotales', 'comprasPagadas', 'saldoProveedor'));
    }

    public function edit(Proveedor $proveedor): View
    {
        $departamentos = ColombiaHelper::departamentos();
        $tiposId       = ColombiaHelper::tiposIdentificacion();
        $municipios    = $proveedor->departamento
            ? ColombiaHelper::municipiosDe($proveedor->departamento)
            : [];
        return view('proveedores.edit', compact('proveedor', 'departamentos', 'tiposId', 'municipios'));
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_entidad'        => ['required', Rule::in(['persona', 'empresa'])],
            'tipo_identificacion' => ['required', Rule::in(array_keys(ColombiaHelper::tiposIdentificacion()))],
            'identificacion'      => ['required', 'string', 'max:30', Rule::unique('proveedores')->ignore($proveedor->id)],
            'nombre_razon_social' => ['required', 'string', 'max:80'],
            'telefono'            => ['nullable', 'string', 'max:30'],
            'telefono2'           => ['nullable', 'string', 'max:30'],
            'contacto_nombre'     => ['nullable', 'string', 'max:80'],
            'email'               => ['nullable', 'email', 'max:100'],
            'direccion'           => ['nullable', 'string', 'max:500'],
            'departamento'        => ['nullable', 'string', 'max:60'],
            'municipio'           => ['nullable', 'string', 'max:80'],
            'notas'               => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $proveedor->update($validated);
            return redirect()->route('proveedores.index')
                ->with('success', 'Proveedor actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error actualizando proveedor: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el proveedor.')->withInput();
        }
    }

    public function anular(Proveedor $proveedor): RedirectResponse
    {
        if (auth()->user()->role === 'invitado') {
            return redirect()->route('proveedores.index')->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $proveedor->active = !$proveedor->active;
        $proveedor->save();

        $action = $proveedor->active ? 'reactivado' : 'desactivado';
        return redirect()->back()->with('success', "El proveedor ha sido {$action} exitosamente.");
    }

    /** AJAX: municipios de un departamento */
    public function municipios(Request $request)
    {
        $dep = $request->get('departamento', '');
        return response()->json(ColombiaHelper::municipiosDe($dep));
    }
}