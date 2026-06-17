<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Electronica;
use App\Models\Abono;

class ElectronicaAbonoController extends Controller
{
    /** Guardar un nuevo abono en una electronica */
    public function store(Request $request, Electronica $electronica)
    {
        $validated = $request->validate([
            'monto'      => 'required|numeric|min:0.01',
            'fecha'      => 'required|date',
            'tipo_pago'  => 'required|in:efectivo,consignacion',
            'descripcion'=> 'nullable|string|max:500',
        ]);

        $validated['electronica_id'] = $electronica->id;
        $validated['user_id'] = auth()->id();

        $abono = Abono::create($validated);

        // Integración Operación Diaria: Registrar el abono automáticamente en la Caja
        $concepto = \App\Models\ConceptoCaja::firstOrCreate(['nombre' => 'Abono Electrónica']);
        \App\Models\MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'fecha' => $validated['fecha'],
            'monto' => $validated['monto'],
            'concepto_id' => $concepto->id,
            'persona' => $electronica->equipo->cliente->nombre ?? 'Cliente Electrónica',
            'descripcion' => 'Abono autom. ELC ' . $electronica->id_orden . ($validated['descripcion'] ? ' - ' . $validated['descripcion'] : ''),
            'tipo_pago' => $validated['tipo_pago'],
            'estado' => 'activo',
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', "Abono de $" . number_format($validated['monto'], 0, ',', '.') . " registrado y añadido a caja correctamente.");
    }

    /** Eliminar un abono */
    public function destroy(Abono $abono)
    {
        $electronicaId = $abono->electronica_id;
        
        // Buscar y eliminar el movimiento de caja asociado (búsqueda aproximada por monto y fecha)
        $concepto = \App\Models\ConceptoCaja::where('nombre', 'Abono Electrónica')->first();
        if ($concepto) {
            \App\Models\MovimientoCaja::where('concepto_id', $concepto->id)
                ->where('monto', $abono->monto)
                ->where('fecha', $abono->fecha->toDateString())
                ->where('descripcion', 'like', "%ELC " . $abono->electronica->id_orden . "%")
                ->delete();
        }

        $abono->delete();
        return back()->with('success', 'Abono eliminado correctamente y su ingreso removido de la caja.');
    }
}
