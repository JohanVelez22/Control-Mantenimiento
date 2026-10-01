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

            // `diferencia` queda NOT NULL DEFAULT 0 a propósito. La señal de que un
            // cierre fue físicamente conciliado es `efectivo_real_contado` (nullable),
            // no la diferencia. Un cierre sin conteo tiene diferencia 0 pero
            // efectivo_real_contado NULL, y se reporta como 'sin_conciliar'. Así no se
            // altera el esquema de instalaciones ya migradas.
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
