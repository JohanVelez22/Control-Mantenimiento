<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuracion = Configuracion::first() ?? new Configuracion();
        return view('configuracion.index', compact('configuracion'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'nit' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'correo' => 'nullable|email|max:255',
            'pie_pagina_factura' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $configuracion = Configuracion::first() ?? new Configuracion();
        
        $configuracion->nombre = $request->nombre;
        $configuracion->nit = $request->nit;
        $configuracion->telefono = $request->telefono;
        $configuracion->direccion = $request->direccion;
        $configuracion->correo = $request->correo;
        $configuracion->pie_pagina_factura = $request->pie_pagina_factura;

        if ($request->hasFile('logo')) {
            if ($configuracion->logo_path) {
                Storage::disk('public')->delete($configuracion->logo_path);
            }
            $configuracion->logo_path = $request->file('logo')->store('configuracion', 'public');
        }

        $configuracion->save();

        return redirect()->back()->with('success', 'Configuración de la empresa guardada correctamente.');
    }
}
