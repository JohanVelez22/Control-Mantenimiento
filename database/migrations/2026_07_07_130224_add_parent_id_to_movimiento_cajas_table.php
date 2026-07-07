<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            // Relación reflexiva para registrar abonos parciales a un movimiento padre
            $table->unsignedBigInteger('parent_id')->nullable()->after('abono_id');
            $table->foreign('parent_id')->references('id')->on('movimiento_cajas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
