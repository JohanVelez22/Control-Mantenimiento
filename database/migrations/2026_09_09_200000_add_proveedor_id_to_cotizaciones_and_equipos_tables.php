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
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            if (! Schema::hasColumn('cotizaciones', 'proveedor_id')) {
                $table->foreignId('proveedor_id')->nullable()->after('cliente_id')->constrained('proveedores')->nullOnDelete();
            }
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            if (! Schema::hasColumn('equipos', 'proveedor_id')) {
                $table->foreignId('proveedor_id')->nullable()->after('cliente_id')->constrained('proveedores')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // D-21: volver a cliente_id NOT NULL es imposible si hay equipos o cotizaciones de
        // proveedor; se detiene antes de tocar nada en lugar de dejar el rollback a medias.
        foreach (['cotizaciones', 'equipos'] as $tabla) {
            if (DB::table($tabla)->whereNull('cliente_id')->exists()) {
                throw new RuntimeException(
                    "No se puede revertir: {$tabla} tiene registros de proveedor (cliente_id vacío)."
                );
            }
        }

        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'proveedor_id')) {
                $table->dropForeign(['proveedor_id']);
                $table->dropColumn('proveedor_id');
            }
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });

        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'proveedor_id')) {
                $table->dropForeign(['proveedor_id']);
                $table->dropColumn('proveedor_id');
            }
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });
    }
};
