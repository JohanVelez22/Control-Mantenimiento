<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Mantenimiento;
use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;

class AbonoController extends Controller
{
    /**
     * Guardar un nuevo abono en un mantenimiento.
     * - Crea el registro en `abonos`.
     * - Crea el MovimientoCaja vinculado mediante abono_id (FK exacta).
     */
    public function store(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $request->validate([
            'monto'       => 'required|numeric|min:0.01',
            'fecha'       => 'required|date',
            'tipo_pago'   => 'required|in:efectivo,consignacion',
            'descripcion' => 'nullable|string|max:500',
        ]);

        $validated['mantenimiento_id'] = $mantenimiento->id;
        $validated['user_id']          = auth()->id();

        try {
            DB::beginTransaction();

            $abono = Abono::create($validated);

            // Registrar en Caja vinculado exactamente a este abono
            $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Abono Mantenimiento']);
            MovimientoCaja::create([
                'tipo_movimiento' => 'ingreso',
                'fecha'           => $validated['fecha'],
                'monto'           => $validated['monto'],
                'concepto_id'     => $concepto->id,
                'persona'         => $mantenimiento->equipo->cliente->nombre ?? 'Cliente Mantenimiento',
                'descripcion'     => 'Abono autom. Orden ' . $mantenimiento->id_orden
                                     . ($validated['descripcion'] ? ' — ' . $validated['descripcion'] : ''),
                'tipo_pago'       => $validated['tipo_pago'],
                'estado'          => 'activo',
                'user_id'         => auth()->id(),
                'abono_id'        => $abono->id,   // ← FK exacta (soluciona bug de borrado frágil)
            ]);

            DB::commit();

            return back()->with('success',
                'Abono de $' . number_format($validated['monto'], 0, ',', '.') . ' registrado y añadido a caja correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando abono de mantenimiento: ' . $e->getMessage());
            return back()->with('error', 'Error al registrar el abono. Intenta de nuevo.');
        }
    }

    /**
     * Eliminar un abono.
     * - Borra el MovimientoCaja usando la FK exacta abono_id (seguro y preciso).
     * - Elimina el abono.
     */
    public function destroy(Abono $abono)
    {
        try {
            DB::beginTransaction();

            // Eliminar el MovimientoCaja asociado por FK exacta (no por búsqueda aproximada)
            MovimientoCaja::where('abono_id', $abono->id)->delete();

            $abono->delete();

            DB::commit();

            return back()->with('success', 'Abono eliminado correctamente y su ingreso removido de la caja.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando abono de mantenimiento: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el abono. Intenta de nuevo.');
        }
    }
}
