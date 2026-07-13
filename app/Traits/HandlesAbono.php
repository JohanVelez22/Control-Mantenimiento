<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;

trait HandlesAbono
{
    /**
     * Registrar un abono en un modelo (Mantenimiento o Electronica)
     * y crear el MovimientoCaja correspondiente.
     */
    protected function storeAbono($model, \Illuminate\Http\Request $request, string $conceptoNombre, string $successMsg): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'monto'       => 'required|numeric|min:0.01',
            'fecha'       => 'required|date',
            'tipo_pago'   => 'required|in:efectivo,consignacion',
            'descripcion' => 'nullable|string|max:500',
        ]);

        // No permitir abonar más de lo que se debe
        $saldoPendiente = $model->saldo_pendiente;
        if ($validated['monto'] > $saldoPendiente + 0.001) {
            return back()->with('error',
                'El abono ($' . number_format($validated['monto'], 0, ',', '.') .
                ') no puede superar el saldo pendiente ($' . number_format($saldoPendiente, 0, ',', '.') . ').')->withInput();
        }

        // El ID del campo FK depende del modelo
        $fkField = $model instanceof \App\Models\Mantenimiento ? 'mantenimiento_id' : 'electronica_id';
        $validated[$fkField] = $model->id;
        $validated['user_id'] = auth()->id();

        try {
            DB::beginTransaction();

            $abono = Abono::create($validated);

            // Registrar en Caja vinculado exactamente a este abono
            $concepto = ConceptoCaja::firstOrCreate(['nombre' => $conceptoNombre]);
            MovimientoCaja::create([
                'tipo_movimiento' => 'ingreso',
                'fecha'           => $validated['fecha'],
                'monto'           => $validated['monto'],
                'concepto_id'     => $concepto->id,
                'persona'         => $this->getPersona($model),
                'descripcion'     => $this->getDescripcion($model, $abono, $validated['descripcion'] ?? null),
                'tipo_pago'       => $validated['tipo_pago'],
                'estado'          => 'activo',
                'user_id'         => auth()->id(),
                'abono_id'        => $abono->id,
            ]);

            DB::commit();

            return back()->with('success',
                'Abono de $' . number_format($validated['monto'], 0, ',', '.') . ' registrado y añadido a caja correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando abono: ' . $e->getMessage());
            return back()->with('error', 'Error al registrar el abono. Intenta de nuevo.');
        }
    }

    /**
     * Eliminar un abono y su MovimientoCaja asociado
     */
    protected function destroyAbono(Abono $abono, string $successMsg): \Illuminate\Http\RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Eliminar el MovimientoCaja asociado por FK exacta
            MovimientoCaja::where('abono_id', $abono->id)->delete();

            $abono->delete();

            DB::commit();

            return back()->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando abono: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el abono. Intenta de nuevo.');
        }
    }

    /**
     * Obtener nombre de persona para MovimientoCaja
     */
    protected function getPersona($model): string
    {
        if ($model instanceof \App\Models\Mantenimiento) {
            return $model->equipo->cliente->nombre ?? 'Cliente Mantenimiento';
        }
        return $model->equipo->cliente->nombre ?? 'Cliente Electrónica';
    }

    /**
     * Obtener descripción para MovimientoCaja
     */
    protected function getDescripcion($model, Abono $abono, ?string $descripcion): string
    {
        $prefix = $model instanceof \App\Models\Mantenimiento ? 'Abono autom. Orden ' : 'Abono autom. ELC ';
        $desc = $prefix . $model->id_orden;
        return $desc . ($descripcion ? ' — ' . $descripcion : '');
    }
}