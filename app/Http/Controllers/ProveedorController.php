<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $query = Proveedor::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nombre_razon_social', 'like', "%{$s}%")
                  ->orWhere('identificacion', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_entidad', $request->tipo);
        }

        $proveedores = $query->orderBy('nombre_razon_social')->paginate(15);

        return view('proveedores.index', compact('proveedores'));
    }

    public function create(): View
    {
        return view('proveedores.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_entidad'        => ['required', Rule::in(['persona', 'empresa'])],
            'identificacion'      => ['required', 'string', 'max:20', 'unique:proveedores,identificacion'],
            'nombre_razon_social' => ['required', 'string', 'max:255'],
            'telefono'            => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:255'],
            'direccion'           => ['nullable', 'string', 'max:500'],
            'notas'               => ['nullable', 'string'],
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
        $comprasTotales  = $proveedor->facturas()->where('tipo_movimiento', 'compra')->sum('total_documento');
        $comprasPagadas  = $proveedor->facturas()->where('tipo_movimiento', 'compra')->sum('total_pagado');
        $saldoProveedor  = $comprasTotales - $comprasPagadas;

        return view('proveedores.show', compact('proveedor', 'comprasTotales', 'comprasPagadas', 'saldoProveedor'));
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $validated = $request->validate([
            'tipo_entidad'        => ['required', Rule::in(['persona', 'empresa'])],
            'identificacion'      => ['required', 'string', 'max:20', Rule::unique('proveedores')->ignore($proveedor->id)],
            'nombre_razon_social' => ['required', 'string', 'max:255'],
            'telefono'            => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:255'],
            'direccion'           => ['nullable', 'string', 'max:500'],
            'notas'               => ['nullable', 'string'],
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

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        try {
            // SoftDelete — no elimina si tiene stocks activos
            if ($proveedor->stocks()->exists()) {
                return back()->with('error', 'No se puede eliminar el proveedor porque tiene artículos de inventario asociados.');
            }
            $proveedor->delete();
            return redirect()->route('proveedores.index')
                ->with('success', 'Proveedor eliminado.');
        } catch (\Exception $e) {
            Log::error('Error eliminando proveedor: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el proveedor.');
        }
    }
}
