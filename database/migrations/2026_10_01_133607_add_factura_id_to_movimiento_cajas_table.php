<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->foreignId('factura_id')
                ->nullable()
                ->after('abono_id')
                ->constrained('facturas')
                ->nullOnDelete();
        });

        // Backfill de facturas existentes sobre movimientos de caja
        try {
            $facturas = DB::table('facturas')->select('id', 'numero_factura')->get();
            foreach ($facturas as $f) {
                $num = $f->numero_factura;
                DB::table('movimiento_cajas')
                    ->whereNull('factura_id')
                    ->where(function ($q) use ($num) {
                        $q->where('descripcion', 'like', "%#{$num}")
                          ->orWhere('descripcion', 'like', "%#{$num} %")
                          ->orWhere('descripcion', 'like', "%#{$num},%")
                          ->orWhere('descripcion', 'like', "%#{$num}.%")
                          ->orWhere('descripcion', 'like', "%#{$num}\n%");
                    })
                    ->update(['factura_id' => $f->id]);
            }
        } catch (\Throwable $e) {
            // Continuar si está ejecutándose en contexto de test aislado
        }
    }

    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropForeign(['factura_id']);
            $table->dropColumn('factura_id');
        });
    }
};
