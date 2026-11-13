<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Asigna factura_id a los pagos antiguos que solo se vinculaban a su factura por el texto
 * "#VT-n"/"#CP-n" de la descripción (M-01/M-02). Desde esta versión el vínculo es SOLO por
 * factura_id: un duplicado o un ingreso manual que mencione la referencia ya no paga la factura.
 *
 * Solo toca filas sin factura_id ni parent_id cuya descripción termina exactamente en la
 * referencia (con los mismos separadores que usaba el sistema) y del tipo que corresponde
 * a la factura. No modifica montos, fechas ni estados. Reversible: down() deja en NULL solo
 * las filas que esta migración vinculó (registradas en la tabla de control).
 */
return new class extends Migration
{
    public function up(): void
    {
        $vinculadas = [];

        foreach (DB::table('facturas')->select('id', 'numero_factura', 'tipo_movimiento')->orderBy('id')->get() as $factura) {
            $ref = '#'.$factura->numero_factura;
            $tipo = $factura->tipo_movimiento === 'venta' ? 'ingreso' : 'egreso';

            $ids = DB::table('movimiento_cajas')
                ->whereNull('factura_id')
                ->whereNull('parent_id')
                ->where('tipo_movimiento', $tipo)
                ->where(function ($q) use ($ref) {
                    foreach (['', ' %', ',%', '.%', ')%', "\n%"] as $sufijo) {
                        $q->orWhere('descripcion', 'like', '%'.$ref.$sufijo);
                    }
                })
                ->pluck('id');

            foreach ($ids as $id) {
                $vinculadas[$id] = $factura->id;
            }
        }

        foreach ($vinculadas as $movId => $facturaId) {
            DB::table('movimiento_cajas')->where('id', $movId)->whereNull('factura_id')->update(['factura_id' => $facturaId]);
        }

        if ($vinculadas) {
            DB::table('eventos')->insert([
                'user_id' => null,
                'accion' => 'editar',
                'modelo_tipo' => 'MovimientoCaja',
                'modelo_id' => null,
                'descripcion' => 'Migración: '.count($vinculadas).' pago(s) antiguos vinculados a su factura por factura_id. IDs: '.implode(',', array_keys($vinculadas)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $evento = DB::table('eventos')->where('descripcion', 'like', 'Migración: % pago(s) antiguos vinculados a su factura%')->latest('id')->first();
        if ($evento && preg_match('/IDs: ([\d,]+)/', $evento->descripcion, $m)) {
            DB::table('movimiento_cajas')->whereIn('id', explode(',', $m[1]))->update(['factura_id' => null]);
        }
    }
};
