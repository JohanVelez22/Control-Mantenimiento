<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->decimal('efectivo_real_contado', 12, 2)->nullable()->after('efectivo');
            $table->decimal('diferencia', 12, 2)->default(0)->after('efectivo_real_contado');
            $table->text('motivo_diferencia')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropColumn(['efectivo_real_contado', 'diferencia', 'motivo_diferencia']);
        });
    }
};
