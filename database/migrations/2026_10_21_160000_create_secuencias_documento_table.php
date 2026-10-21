<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('secuencias_documento', function (Blueprint $table) {
            $table->string('prefijo', 20)->primary();
            $table->unsignedBigInteger('valor')->default(0);
            $table->timestamps();
        });

        // Prefijos conocidos del sistema y tablas asociadas
        $prefijos = [
            ['prefijo' => 'ORD-', 'tabla' => 'mantenimientos', 'columna' => 'id_orden'],
            ['prefijo' => 'ELC-', 'tabla' => 'electronicas',    'columna' => 'id_orden'],
            ['prefijo' => 'COT-', 'tabla' => 'cotizaciones',    'columna' => 'codigo'],
            ['prefijo' => 'VT-',  'tabla' => 'facturas',        'columna' => 'numero_factura'],
            ['prefijo' => 'CP-',  'tabla' => 'facturas',        'columna' => 'numero_factura'],
            ['prefijo' => 'F-',   'tabla' => 'facturas',        'columna' => 'numero_factura'],
        ];

        foreach ($prefijos as $p) {
            $max = 0;
            if (Schema::hasTable($p['tabla'])) {
                $rows = DB::table($p['tabla'])
                    ->where($p['columna'], 'like', $p['prefijo'].'%')
                    ->pluck($p['columna']);

                foreach ($rows as $r) {
                    if (preg_match('/(\d+)\s*$/', (string) $r, $matches)) {
                        $num = (int) $matches[1];
                    } else {
                        $num = (int) preg_replace('/[^0-9]/', '', (string) $r);
                    }
                    if ($num > $max) {
                        $max = $num;
                    }
                }
            }

            DB::table('secuencias_documento')->insert([
                'prefijo' => $p['prefijo'],
                'valor' => $max,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secuencias_documento');
    }
};
