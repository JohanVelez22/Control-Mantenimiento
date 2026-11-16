<?php

namespace App\Http\Controllers;

use App\Models\ConceptoCaja;
use App\Services\AnulacionService;
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

        return redirect()->route('conceptos.index')->with('success', 'Concepto creado correctamente.');
    }

    public function edit(ConceptoCaja $concepto)
    {
        return view('caja.conceptos.edit', compact('concepto'));
    }

    public function update(Request $request, ConceptoCaja $concepto)
    {
        if ($concepto->es_del_sistema) {
            return redirect()->route('conceptos.index')->with('error', 'El concepto "'.$concepto->nombre.'" lo usa el sistema para abonos, ventas o compras: no se puede renombrar.');
        }

        $request->validate(['nombre' => 'required|string|max:80|unique:concepto_cajas,nombre,'.$concepto->id]);
        $concepto->update(['nombre' => trim($request->nombre)]);

        return redirect()->route('conceptos.index')->with('success', 'Concepto actualizado correctamente.');
    }

    public function destroy(Request $request, ConceptoCaja $concepto)
    {
        if ($error = app(AnulacionService::class)->autorizarOperacionSensible($request)) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        if ($concepto->es_del_sistema) {
            return redirect()->route('conceptos.index')->with('error', 'El concepto "'.$concepto->nombre.'" lo usa el sistema: no se puede eliminar.');
        }

        if ($concepto->movimientos()->exists()) {
            return redirect()->route('conceptos.index')->with('error', 'No se puede eliminar el concepto porque tiene movimientos de caja asociados.');
        }
        $concepto->delete();

        return redirect()->route('conceptos.index')->with('success', 'Concepto eliminado correctamente.');
    }
}
