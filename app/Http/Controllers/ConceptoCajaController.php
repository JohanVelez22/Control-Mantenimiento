<?php

namespace App\Http\Controllers;

use App\Models\ConceptoCaja;
use Illuminate\Http\Request;

class ConceptoCajaController extends Controller
{
    public function index()
    {
        $conceptos = ConceptoCaja::orderBy('nombre')->get();
        return view('caja.conceptos.index', compact('conceptos'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:80|unique:concepto_cajas,nombre']);
        ConceptoCaja::create(['nombre' => trim($request->nombre)]);
        return redirect()->back()->with('success', 'Concepto creado correctamente.');
    }

    public function update(Request $request, ConceptoCaja $concepto)
    {
        $request->validate(['nombre' => 'required|string|max:80|unique:concepto_cajas,nombre,'.$concepto->id]);
        $concepto->update(['nombre' => trim($request->nombre)]);
        return redirect()->back()->with('success', 'Concepto actualizado correctamente.');
    }

    public function destroy(ConceptoCaja $concepto)
    {
        if ($concepto->movimientos()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar el concepto porque tiene movimientos de caja asociados.');
        }
        $concepto->delete();
        return redirect()->back()->with('success', 'Concepto eliminado correctamente.');
    }
}
