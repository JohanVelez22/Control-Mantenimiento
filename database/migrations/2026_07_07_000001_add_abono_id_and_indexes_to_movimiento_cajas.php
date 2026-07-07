<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría 2026-07-07
 * 
 * 1. Agrega columna `abono_id` (nullable FK) a movimiento_cajas.
 *    - Permite enlazar un MovimientoCaja exactamente con su Abono origen.
 *    - Soluciona el bug crítico donde destroy() buscaba por monto+fecha (frágil).
 *    - Es nullable para no afectar movimientos existentes ni manuales.
 * 
 * 2. Agrega índices de rendimiento:
 *    - movimiento_cajas(fecha)              → consultas del Dashboard
 *    - movimiento_cajas(tipo_movimiento)    → filtros de reportes
 *    - movimiento_cajas(anulado)            → filtros globales
 *    - abonos(mantenimiento_id)             → eager-loading de abonos
 *    - abonos(electronica_id)              → eager-loading de abonos
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            // FK opcional hacia abonos — la columna es nullable para compatibilidad
            // con todos los movimientos existentes y futuros manuales.
            $table->unsignedBigInteger('abono_id')->nullable()->after('user_id');
            $table->foreign('abono_id')->references('id')->on('abonos')->nullOnDelete();

            // Índices de rendimiento para queries frecuentes
            $table->index('fecha',            'idx_movcaja_fecha');
            $table->index('tipo_movimiento',  'idx_movcaja_tipo');
            $table->index('anulado',          'idx_movcaja_anulado');
        });

        Schema::table('abonos', function (Blueprint $table) {
            // Índices para eager-loading eficiente desde Mantenimiento y Electronica
            $table->index('mantenimiento_id', 'idx_abonos_mantenimiento');
            $table->index('electronica_id',   'idx_abonos_electronica');
        });
    }

    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropForeign(['abono_id']);
            $table->dropColumn('abono_id');
            $table->dropIndex('idx_movcaja_fecha');
            $table->dropIndex('idx_movcaja_tipo');
            $table->dropIndex('idx_movcaja_anulado');
        });

        Schema::table('abonos', function (Blueprint $table) {
            $table->dropIndex('idx_abonos_mantenimiento');
            $table->dropIndex('idx_abonos_electronica');
        });
    }
};
