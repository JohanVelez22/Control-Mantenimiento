<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hacer mantenimiento_id nullable y agregar electronica_id en abonos
        Schema::table('abonos', function (Blueprint $table) {
            $table->foreignId('mantenimiento_id')->nullable()->change();
            $table->foreignId('electronica_id')->nullable()->after('mantenimiento_id')->constrained('electronicas')->onDelete('cascade');
        });

        // Crear la tabla pivote electronica_stock
        Schema::create('electronica_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronica_id')->constrained('electronicas')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronica_stock');

        Schema::table('abonos', function (Blueprint $table) {
            $table->dropForeign(['electronica_id']);
            $table->dropColumn('electronica_id');
            // Hacer mantenimiento_id requerido nuevamente no es seguro si hay nulos, 
            // pero lo intentamos
            $table->foreignId('mantenimiento_id')->nullable(false)->change();
        });
    }
};
