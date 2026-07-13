<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Abono;

trait HandlesAbonos
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $parent (Mantenimiento|Electronica)
     * @param array $validated ['monto', 'fecha', 'tipo_pago', 'descripcion?']
     * @param string $conceptName
     * @param string $successMessage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAbono($parent, array $validated, string $conceptName, string $successMessage)
    {
        $saldoPendiente = $parent->saldo_pendiente;
        if ($validated['monto'] > $saldoPendiente + 0.001) {
            return back()->with('error',
                'El abono ($' . number_format($validated['monto'], 0, ',', '.') .
                ') no puede superar el saldo pendiente ($' . number_format($saldoPendiente, 0, ',', '.') . ').')->withInput();
        }

        $validated[$parent->getForeignKey()] = $parent->id;
        $validated['user_id'] = auth()->id();

        try {
            DB::transaction(function () use ($parent, $validated, $conceptName) {
                $abono = Abono::create($validated);

                $concepto = ConceptoCaja::firstOrCreate(['nombre' => $conceptName]);
                MovimientoCaja::create([
                    'tipo_movimiento' => 'ingreso',
                    'fecha'           => $validated['fecha'],
                    'monto'           => $validated['monto'],
                    'concepto_id'     => $concepto->id,
                    'persona'         => $this->getParentPersona($parent),
                    'descripcion'     => 'Abono autom. ' . $parent->getOrderLabel()
                                        . ($validated['descripcion'] ? ' — ' . $validated['descripcion'] : ''),
                    'tipo_pago'       => $validated['tipo_pago'],
                    'estado'          => 'activo',
                    'user_id'         => auth()->id(),
                    'abono_id'        => $abono->id,
                ]);
            });

            return back()->with('success',
                'Abono de $' . number_format($validated['monto'], 0, ',', '.') . ' registrado y añadido a caja correctamente.');
        } catch (\Exception $e) {
            Log::error('Error registrando abono: ' . $e->getMessage());
            return back()->with('error', 'Error al registrar el abono. Intenta de nuevo.');
        }
    }

    /**
     * @param \App\Models\Abono $abono
     * @param string $successMessage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAbono(Abono $abono, string $successMessage)
    {
        try {
            DB::transaction(function () use ($abono) {
                MovimientoCaja::where('abono_id', $abono->id)->delete();
                $abono->delete();
            });

            return back()->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Error eliminando abono: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el abono. Intenta de nuevo.');
        }
    }

    /**
     * Override in child controller to return the persona name
     */
    abstract protected function getParentPersona($parent): string;

    /**
     * Override in child controller to return the order label
     */
    abstract protected function getOrderLabel($parent): string;
}