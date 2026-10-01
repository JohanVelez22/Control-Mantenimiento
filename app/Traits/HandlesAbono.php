<?php

namespace App\Traits;

use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Services\CierreCajaGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesAbono
{
    /**
     * Registrar un abono en un modelo (Mantenimiento o Electronica)
     * y crear el MovimientoCaja correspondiente.
     */
    protected function storeAbono($model, Request $request, string $conceptoNombre, string $successMsg): RedirectResponse
    {
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'tipo_pago' => 'required|in:efectivo,consignacion',
            'descripcion' => 'nullable|string|max:500',
        ]);

        // No permitir abonar a registros anulados
        if ($model->anulado || ($model->estado ?? null) === 'anulado') {
            return back()->with('error', 'No se pueden registrar abonos a un registro anulado.')->withInput();
        }

        // No permitir abonar más de lo que se debe
        $saldoPendiente = $model->saldo_pendiente;
        if ($validated['monto'] > $saldoPendiente + \App\Models\Factura::EPSILON) {
            return back()->with('error',
                'El abono ($'.number_format($validated['monto'], 0, ',', '.').
                ') no puede superar el saldo pendiente ($'.number_format($saldoPendiente, 0, ',', '.').').')->withInput();
        }

        // Bloqueo de período: el abono genera un movimiento de caja, así que la fecha
        // no puede pertenecer a un día ya cerrado.
        if (CierreCajaGuard::fechaEstaCerrada($validated['fecha'])) {
            return back()->withErrors([
                'fecha' => 'No se pueden registrar abonos con fecha '.$validated['fecha'].' porque ese día ya tiene cierre de caja registrado.',
            ])->withInput();
        }

        // El ID del campo FK depende del modelo
        $fkField = $model instanceof Mantenimiento ? 'mantenimiento_id' : 'electronica_id';
        $validated[$fkField] = $model->id;
        $validated['user_id'] = auth()->id();

        try {
            DB::beginTransaction();

            if (CierreCajaGuard::fechaEstaCerrada($validated['fecha'])) {
                DB::rollBack();

                return back()->withErrors([
                    'fecha' => 'No se pueden registrar abonos con fecha '.$validated['fecha'].' porque ese día ya tiene cierre de caja registrado.',
                ])->withInput();
            }

            $abono = Abono::create($validated);

            // Determinar si es un pago completo (total/final) o abono parcial
            $esPagoCompleto = ($validated['monto'] >= $saldoPendiente - \App\Models\Factura::EPSILON);
            $totalAbonosCount = $model->abonos()->count();

            if ($esPagoCompleto && $totalAbonosCount === 1) {
                $conceptoNombreFinal = $model instanceof Mantenimiento ? 'Pago Mantenimiento' : 'Pago Electrónica';
                $prefix = $model instanceof Mantenimiento ? 'Pago Total Orden ' : 'Pago Total ELC ';
            } elseif ($esPagoCompleto) {
                $conceptoNombreFinal = $model instanceof Mantenimiento ? 'Pago Mantenimiento' : 'Pago Electrónica';
                $prefix = $model instanceof Mantenimiento ? 'Pago Final Orden ' : 'Pago Final ELC ';
            } else {
                $conceptoNombreFinal = $conceptoNombre; // 'Abono Mantenimiento' / 'Abono Electrónica'
                $prefix = $model instanceof Mantenimiento ? 'Abono Parcial Orden ' : 'Abono Parcial ELC ';
            }

            $descUser = $validated['descripcion'] ?? null;
            $descripcionFinal = $prefix.$model->id_orden.($descUser ? ' — '.$descUser : '');

            // Registrar en Caja vinculado exactamente a este abono
            $concepto = ConceptoCaja::firstOrCreate(['nombre' => $conceptoNombreFinal]);
            MovimientoCaja::create([
                'tipo_movimiento' => 'ingreso',
                'fecha' => $validated['fecha'],
                'monto' => $validated['monto'],
                'concepto_id' => $concepto->id,
                'persona' => $this->getPersona($model),
                'descripcion' => $descripcionFinal,
                'tipo_pago' => $validated['tipo_pago'],
                'estado' => 'activo',
                'user_id' => auth()->id(),
                'abono_id' => $abono->id,
            ]);

            DB::commit();

            $tipoMsg = $esPagoCompleto ? 'Pago' : 'Abono';

            return back()->with('success',
                $tipoMsg.' de $'.number_format($validated['monto'], 0, ',', '.').' registrado y añadido a caja correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando abono: '.$e->getMessage());

            return back()->with('error', 'Error al registrar el movimiento. Intenta de nuevo.');
        }
    }

    /**
     * Eliminar un abono y su MovimientoCaja asociado
     */
    protected function destroyAbono(Abono $abono, string $successMsg): RedirectResponse
    {
        if ($error = app(\App\Services\AnulacionService::class)->autorizarOperacionSensible(request())) {
            return back()->with('error', $error);
        }

        $fechaAbono = $abono->fecha ? \Carbon\Carbon::parse($abono->fecha)->toDateString() : null;
        if (CierreCajaGuard::fechaEstaCerrada($fechaAbono)) {
            return back()->with('error', 'No se puede eliminar este abono: la fecha '.$fechaAbono.' ya tiene un cierre de caja registrado. Para modificarlo, elimine primero el cierre de ese día.');
        }

        try {
            DB::beginTransaction();

            if (CierreCajaGuard::fechaEstaCerrada($fechaAbono)) {
                DB::rollBack();

                return back()->with('error', 'No se puede eliminar este abono: la fecha '.$fechaAbono.' ya tiene un cierre de caja registrado.');
            }

            // Anular lógicamente el MovimientoCaja asociado vía modelo para preservar la trazabilidad contable y auditoría
            $mov = MovimientoCaja::where('abono_id', $abono->id)->first();
            if ($mov) {
                $mov->update([
                    'anulado' => true,
                    'estado' => 'anulado',
                ]);
            }

            $abono->delete();

            DB::commit();

            return back()->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando abono: '.$e->getMessage());

            return back()->with('error', 'Error al eliminar el abono. Intenta de nuevo.');
        }
    }

    /**
     * Obtener nombre de persona para MovimientoCaja
     */
    protected function getPersona($model): string
    {
        if ($model instanceof Mantenimiento) {
            return $model->equipo->cliente->nombre ?? 'Cliente Mantenimiento';
        }

        return $model->equipo->cliente->nombre ?? 'Cliente Electrónica';
    }

    /**
     * Obtener descripción para MovimientoCaja
     */
    protected function getDescripcion($model, Abono $abono, ?string $descripcion): string
    {
        $prefix = $model instanceof Mantenimiento ? 'Abono autom. Orden ' : 'Abono autom. ELC ';
        $desc = $prefix.$model->id_orden;

        return $desc.($descripcion ? ' — '.$descripcion : '');
    }
}
