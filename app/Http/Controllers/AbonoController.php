<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mantenimiento;
use App\Models\Abono;

class AbonoController extends Controller
{
    /** Guardar un nuevo abono en un mantenimiento */
    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'monto'      => 'required|numeric|min:0.01',
            'fecha'      => 'required|date',
            'tipo_pago'  => 'required|in:efectivo,consignacion',
            'descripcion'=> 'nullable|string|max:500',
        ]);

        $validated['mantenimiento_id'] = $mantenimiento->id;
        $validated['user_id'] = auth()->id();

        Abono::create($validated);

        return back()->with('success', "Abono de $" . number_format($validated['monto'], 0, ',', '.') . " registrado correctamente.");
    }

    /** Eliminar un abono */
    public function destroy(Abono $abono)
    {
        $mantenimientoId = $abono->mantenimiento_id;
        $abono->delete();
        return back()->with('success', 'Abono eliminado correctamente.');
    }
}
