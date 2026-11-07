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
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->index('estado');
            // Adding a composite index for state and user often queried together
            $table->index(['estado', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Una migración posterior (2026_10_21) elimina el índice simple de estado: borrar solo los que existan.
        foreach (['cotizaciones_estado_index', 'cotizaciones_estado_user_id_index'] as $indice) {
            if (Schema::hasIndex('cotizaciones', $indice)) {
                Schema::table('cotizaciones', fn (Blueprint $table) => $table->dropIndex($indice));
            }
        }
    }
};
