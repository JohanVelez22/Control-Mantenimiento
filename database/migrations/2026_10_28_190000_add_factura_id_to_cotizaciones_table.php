<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (! Schema::hasColumn('cotizaciones', 'factura_id')) {
                $table->foreignId('factura_id')->nullable()->after('user_id')->constrained('facturas')->nullOnDelete();
            }
        });

        // Backfill para cotizaciones aprobadas existentes vinculadas a facturas por código
        try {
            $aprobadas = DB::table('cotizaciones')->where('estado', 'aprobada')->whereNull('factura_id')->get();
            foreach ($aprobadas as $cot) {
                $factura = DB::table('facturas')
                    ->where('observaciones', 'like', "%Cotización #{$cot->codigo}%")
                    ->first();
                if ($factura) {
                    DB::table('cotizaciones')->where('id', $cot->id)->update(['factura_id' => $factura->id]);
                }
            }
        } catch (\Throwable $e) {
            // Silencioso en entornos nuevos
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'factura_id')) {
                $table->dropForeign(['factura_id']);
                $table->dropColumn('factura_id');
            }
        });
    }
};
