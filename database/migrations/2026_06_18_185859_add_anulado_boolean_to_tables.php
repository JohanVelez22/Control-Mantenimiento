<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->boolean('anulado')->default(false)->after('estado');
        });

        Schema::table('electronicas', function (Blueprint $table) {
            $table->boolean('anulado')->default(false)->after('estado');
        });

        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->boolean('anulado')->default(false)->after('estado');
        });

        // Migrar datos existentes
        DB::table('mantenimientos')->where('estado', 'anulado')->update([
            'anulado' => true,
            'estado' => 'pendiente'
        ]);

        DB::table('electronicas')->where('estado', 'anulado')->update([
            'anulado' => true,
            'estado' => 'pendiente'
        ]);

        DB::table('movimiento_cajas')->where('estado', 'anulado')->update([
            'anulado' => true,
            'estado' => 'activo'
        ]);
    }

    public function down(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn('anulado');
        });

        Schema::table('electronicas', function (Blueprint $table) {
            $table->dropColumn('anulado');
        });

        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropColumn('anulado');
        });
    }
};
