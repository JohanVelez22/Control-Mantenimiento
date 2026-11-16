<?php

use App\Support\CodigoConsulta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S-04: código de consulta aleatorio por orden. El invitado consulta su orden con su
 * cédula y este código (impreso en el recibo), no con el número de orden, que es
 * secuencial y se podía recorrer conociendo una cédula.
 *
 * ARQ-12: stock mínimo por producto (si está vacío se usa el mínimo global de 5).
 *
 * Las órdenes existentes reciben un código nuevo. Reversible: down() elimina las columnas.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mantenimientos', 'electronicas'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'codigo_consulta')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->string('codigo_consulta', 12)->nullable()->unique()->after('id_orden');
                });
            }

            DB::table($tabla)->whereNull('codigo_consulta')->orderBy('id')->select('id')->chunkById(500, function ($filas) use ($tabla) {
                foreach ($filas as $fila) {
                    DB::table($tabla)->where('id', $fila->id)->update(['codigo_consulta' => CodigoConsulta::nuevo($tabla)]);
                }
            });
        }

        if (! Schema::hasColumn('stocks', 'stock_minimo')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->unsignedInteger('stock_minimo')->nullable()->after('cantidad');
            });
        }
    }

    public function down(): void
    {
        foreach (['mantenimientos', 'electronicas'] as $tabla) {
            if (Schema::hasColumn($tabla, 'codigo_consulta')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropUnique(['codigo_consulta']);
                    $table->dropColumn('codigo_consulta');
                });
            }
        }
        if (Schema::hasColumn('stocks', 'stock_minimo')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('stock_minimo');
            });
        }
    }
};
