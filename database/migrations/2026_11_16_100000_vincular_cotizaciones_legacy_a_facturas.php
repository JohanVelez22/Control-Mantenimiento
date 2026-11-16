<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * B-08: las cotizaciones convertidas antes de que existiera `factura_id` se reconocían
 * buscando "Cotización #COT-n" en las observaciones de la factura, lo que confundía COT-1
 * con COT-10. Desde esta versión el enlace es solo por `factura_id`; esta migración lo
 * completa para las antiguas con una coincidencia exacta del código (no por prefijo).
 *
 * No cambia montos ni estados. Reversible: down() deja en NULL solo las filas que enlazó.
 */
return new class extends Migration
{
    public function up(): void
    {
        $enlazadas = [];

        $pendientes = DB::table('cotizaciones')->whereNull('factura_id')->where('estado', 'aprobada')->get(['id', 'codigo']);
        foreach ($pendientes as $cot) {
            $patron = '/Cotizaci[oó]n #'.preg_quote($cot->codigo, '/').'(?![0-9])/u';
            $factura = DB::table('facturas')
                ->where('observaciones', 'like', '%#'.$cot->codigo.'%')
                ->orderBy('id')
                ->get(['id', 'observaciones'])
                ->first(fn ($f) => preg_match($patron, (string) $f->observaciones) === 1);

            if ($factura && ! DB::table('cotizaciones')->where('factura_id', $factura->id)->exists()) {
                DB::table('cotizaciones')->where('id', $cot->id)->whereNull('factura_id')->update(['factura_id' => $factura->id]);
                $enlazadas[] = $cot->id;
            }
        }

        if ($enlazadas) {
            DB::table('eventos')->insert([
                'user_id' => null,
                'accion' => 'editar',
                'modelo_tipo' => 'Cotizacion',
                'modelo_id' => null,
                'descripcion' => 'Migración: '.count($enlazadas).' cotización(es) antiguas enlazadas a su factura por factura_id. IDs: '.implode(',', $enlazadas),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $evento = DB::table('eventos')
            ->where('modelo_tipo', 'Cotizacion')
            ->where('descripcion', 'like', 'Migración: % cotización(es) antiguas enlazadas a su factura%')
            ->orderByDesc('id')
            ->first();
        if (! $evento || ! preg_match('/IDs: ([\d,]+)$/', $evento->descripcion, $m)) {
            return;
        }
        DB::table('cotizaciones')->whereIn('id', array_map('intval', explode(',', $m[1])))->update(['factura_id' => null]);
        DB::table('eventos')->where('id', $evento->id)->delete();
    }
};
