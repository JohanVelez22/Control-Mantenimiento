<?php

namespace App\Traits;

use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Services\AnulacionService;
use App\Services\CierreCajaGuard;
use App\Support\Monto;
use Carbon\Carbon;
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
    protected function storeAbono($model, Request $request, string $conceptoNombre): RedirectResponse
    {
        // B-07 / M-16: el monto se normaliza en el servidor con la regla única ("1.000" = 1000),
        // sin depender del JavaScript del formulario.
        if ($request->has('monto') && ! is_array($request->input('monto'))) {
            $request->merge(['monto' => Monto::parse($request->input('monto'))]);
        }

        $fechaEntrada = $model->fecha_entrada ? Carbon::parse($model->fecha_entrada)->toDateString() : null;
        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:9999999999.99|decimal:0,2',
            // B-07: el abono no puede ser anterior al ingreso de la orden ni futuro
            // (caería en la caja de un día que aún no existe).
            'fecha' => array_values(array_filter(['required', 'date', 'before_or_equal:today', $fechaEntrada ? 'after_or_equal:'.$fechaEntrada : null])),
            'tipo_pago' => 'required|in:efectivo,consignacion',
            'descripcion' => 'nullable|string|max:500',
        ], [
            'fecha.before_or_equal' => 'La fecha del abono no puede ser futura.',
            'fecha.after_or_equal' => 'La fecha del abono no puede ser anterior al ingreso de la orden ('.($fechaEntrada ? Carbon::parse($fechaEntrada)->format('d/m/Y') : '').').',
            'monto.decimal' => 'El monto admite como máximo 2 decimales.',
        ]);

        // No permitir abonar a registros anulados
        if ($model->anulado) {
            return back()->with('error', 'No se pueden registrar abonos a un registro anulado.')->withInput();
        }

        // No permitir abonar más de lo que se debe
        $saldoPendiente = $model->saldo_pendiente;
        if (Factura::superaMonto($validated['monto'], $saldoPendiente)) {
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

            // Bloqueo pesimista del modelo para evitar condiciones de carrera por abonos simultáneos
            $lockedModel = get_class($model)::where('id', $model->id)->lockForUpdate()->first();

            // D-06: revalidar DESPUÉS del bloqueo; una anulación simultánea pudo ganar la carrera
            if (! $lockedModel || $lockedModel->anulado) {
                DB::rollBack();

                return back()->with('error', 'El registro fue anulado mientras se registraba el abono.')->withInput();
            }
            $saldoPendiente = $lockedModel->saldo_pendiente;

            if (Factura::superaMonto($validated['monto'], $saldoPendiente)) {
                DB::rollBack();

                return back()->with('error',
                    'El abono ($'.number_format($validated['monto'], 0, ',', '.').
                    ') no puede superar el saldo pendiente ($'.number_format($saldoPendiente, 0, ',', '.').').')->withInput();
            }

            if (CierreCajaGuard::fechaEstaCerrada($validated['fecha'])) {
                DB::rollBack();

                return back()->withErrors([
                    'fecha' => 'No se pueden registrar abonos con fecha '.$validated['fecha'].' porque ese día ya tiene cierre de caja registrado.',
                ])->withInput();
            }

            $abono = Abono::create($validated);

            // Determinar si es un pago completo (total/final) o abono parcial
            $esPagoCompleto = ($validated['monto'] >= $saldoPendiente - Factura::EPSILON);
            $totalAbonosCount = $model->abonos()->where('anulado', false)->count();

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
                'persona' => $this->getPersona($lockedModel),
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
    protected function destroyAbono(Abono $abono, string $modulo): RedirectResponse
    {
        // B-19: cada módulo anula solo sus propios abonos.
        $fk = $modulo === 'electronica' ? 'electronica_id' : 'mantenimiento_id';
        abort_unless($abono->{$fk}, 404);

        if ($error = app(AnulacionService::class)->autorizarOperacionSensible(request())) {
            return back()->with('error', $error);
        }

        $fechaAbono = $abono->fecha ? Carbon::parse($abono->fecha)->toDateString() : null;
        if (CierreCajaGuard::fechaEstaCerrada($fechaAbono)) {
            return back()->with('error', 'No se puede anular este abono: la fecha '.$fechaAbono.' ya tiene un cierre de caja registrado. Para modificarlo, elimine primero el cierre de ese día.');
        }

        if ($abono->anulado) {
            return back()->with('error', 'Este abono ya se encuentra anulado.');
        }

        // M-09: anular un abono (devolver dinero) exige motivo.
        if ($error = app(AnulacionService::class)->exigirMotivo(request())) {
            return back()->with('error', $error);
        }

        try {
            DB::beginTransaction();

            if (CierreCajaGuard::fechaEstaCerrada($fechaAbono)) {
                DB::rollBack();

                return back()->with('error', 'No se puede anular este abono: la fecha '.$fechaAbono.' ya tiene un cierre de caja registrado.');
            }

            // Doble clic o dos usuarios a la vez: solo una anulación gana.
            $abono = Abono::whereKey($abono->id)->lockForUpdate()->first();
            if (! $abono || $abono->anulado) {
                DB::rollBack();

                return back()->with('error', 'Este abono ya se encuentra anulado.');
            }

            // Anular lógicamente el MovimientoCaja asociado vía modelo para preservar la trazabilidad contable y auditoría
            $mov = MovimientoCaja::where('abono_id', $abono->id)->lockForUpdate()->first();
            if ($mov) {
                $mov->update([
                    'anulado' => true,
                    'estado' => 'anulado',
                ]);
            }

            // Anular lógicamente el Abono (preservando el registro en la BD, la auditoría y la clave foránea)
            app(AnulacionService::class)->registrarMotivo($abono, request(), 'Abono de $'.number_format((float) $abono->monto, 0, ',', '.').' anulado');
            $abono->update([
                'anulado' => true,
            ]);

            DB::commit();

            // F-07: el abono no se borra; queda anulado en el historial y en caja.
            return back()->with('success', 'Abono anulado. Se conserva en el historial y su ingreso en caja quedó anulado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando abono: '.$e->getMessage());

            return back()->with('error', 'Error al anular el abono. Intenta de nuevo.');
        }
    }

    /**
     * Obtener nombre de persona para MovimientoCaja
     */
    protected function getPersona($model): string
    {
        // B-18: el dueño del equipo puede ser un cliente o un proveedor.
        $nombre = $model->equipo?->propietario_nombre;
        if ($nombre && $nombre !== 'N/A') {
            return $nombre;
        }

        return $model instanceof Mantenimiento ? 'Cliente Mantenimiento' : 'Cliente Electrónica';
    }
}
