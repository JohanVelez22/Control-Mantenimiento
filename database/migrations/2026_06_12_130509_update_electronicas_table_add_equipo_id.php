<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar la tabla para evitar problemas de FK con datos viejos sin equipo
        DB::table('electronicas')->truncate();

        Schema::table('electronicas', function (Blueprint $table) {
            $table->dropColumn(['cliente', 'dispositivo', 'marca']);
            $table->foreignId('equipo_id')->after('id_orden')->constrained('equipos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('electronicas', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropColumn('equipo_id');
            $table->string('cliente')->after('id_orden');
            $table->string('dispositivo')->after('cliente');
            $table->string('marca')->nullable()->after('dispositivo');
        });
    }
};
