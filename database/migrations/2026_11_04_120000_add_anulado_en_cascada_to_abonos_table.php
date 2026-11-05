<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue los abonos anulados en cascada (al anular su orden) de los eliminados
 * individualmente. Sin esta marca, reactivar una orden revivía también los abonos
 * que el usuario había eliminado antes, inflando la caja con ingresos inexistentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->boolean('anulado_en_cascada')->default(false)->after('anulado');
        });

        // Datos existentes: un abono anulado de una orden actualmente anulada se
        // considera anulado en cascada (equivale al comportamiento anterior).
        DB::table('abonos')
            ->where('anulado', true)
            ->where(function ($q) {
                $q->whereIn('mantenimiento_id', DB::table('mantenimientos')->where('anulado', true)->select('id'))
                    ->orWhereIn('electronica_id', DB::table('electronicas')->where('anulado', true)->select('id'));
            })
            ->update(['anulado_en_cascada' => true]);

        // Mismo criterio para los pagos de caja de facturas de inventario.
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->boolean('anulado_en_cascada')->default(false)->after('anulado');
        });

        DB::table('movimiento_cajas')
            ->where('anulado', true)
            ->whereIn('factura_id', DB::table('facturas')->where('estado', 'anulada')->select('id'))
            ->update(['anulado_en_cascada' => true]);
    }

    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropColumn('anulado_en_cascada');
        });

        Schema::table('abonos', function (Blueprint $table) {
            $table->dropColumn('anulado_en_cascada');
        });
    }
};
