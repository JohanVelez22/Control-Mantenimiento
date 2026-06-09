<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->string('estado')->default('activo')->after('monto');
        });

        // Modificamos el enum existente de mantenimientos o agregamos a varchar si era enum
        // Si era enum, en MySQL/MariaDB a veces es complicado. Lo más seguro es cambiar a string.
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->string('estado', 50)->default('pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
        
        // Dejar el down() simplificado, no revertir el change() para evitar errores con DBAL
    }
};
