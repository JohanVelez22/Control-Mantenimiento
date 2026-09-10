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
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('estado', 30)->default('operativo')->after('active'); // operativo, dado_de_baja
            $table->string('motivo_baja', 50)->nullable()->after('estado'); // irreparable, desguace_repuestos, chatarrizacion, siniestro, otro
            $table->text('observacion_baja')->nullable()->after('motivo_baja');
            $table->timestamp('fecha_baja')->nullable()->after('observacion_baja');
            $table->foreignId('baja_user_id')->nullable()->after('fecha_baja')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropForeign(['baja_user_id']);
            $table->dropColumn(['estado', 'motivo_baja', 'observacion_baja', 'fecha_baja', 'baja_user_id']);
        });
    }
};
