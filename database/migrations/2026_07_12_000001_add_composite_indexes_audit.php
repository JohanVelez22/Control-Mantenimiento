<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría 2026-07-12 — Índices compuestos de rendimiento.
 *
 * Se añaden índices para las consultas más frecuentes (reportes e index),
 * priorizando el patrón (columna de igualdad, columna de rango) que MySQL
 * aprovecha mejor. Los nombres no colisionan con los índices de
 * 2026_07_07_000001 (idx_movcaja_fecha, idx_movcaja_tipo, idx_movcaja_anulado,
 * idx_abonos_mantenimiento, idx_abonos_electronica).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Mantenimientos: filtros de estado/anulado y rango por fecha de entrada
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->index(['estado', 'anulado'], 'idx_mant_estado_anulado');
            $table->index('fecha_entrada', 'idx_mant_fecha_entrada');
        });

        // Electrónicas: mismos filtros que mantenimientos
        Schema::table('electronicas', function (Blueprint $table) {
            $table->index(['estado', 'anulado'], 'idx_elec_estado_anulado');
            $table->index('fecha_entrada', 'idx_elec_fecha_entrada');
        });

        // Caja: igualdad por tipo + rango por fecha (óptimo para reportes)
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->index(['tipo_movimiento', 'fecha'], 'idx_movcaja_tipo_fecha');
            $table->index(['estado', 'anulado'], 'idx_movcaja_estado_anulado');
        });

        // Facturas: igualdad por tipo + rango por fecha, y tipo + estado
        Schema::table('facturas', function (Blueprint $table) {
            $table->index(['tipo_movimiento', 'fecha'], 'idx_fact_tipo_fecha');
            $table->index(['tipo_movimiento', 'estado'], 'idx_fact_tipo_estado');
        });

        // Stock: listado activo + filtro por categoría
        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['active', 'categoria'], 'idx_stock_active_categoria');
        });

        // Abonos: consultas por fecha
        Schema::table('abonos', function (Blueprint $table) {
            $table->index('fecha', 'idx_abonos_fecha');
        });
    }

    public function down(): void
    {
        // Migraciones posteriores eliminan algunos de estos índices: borrar solo los que existan.
        $indices = [
            ['mantenimientos', 'idx_mant_estado_anulado'],
            ['mantenimientos', 'idx_mant_fecha_entrada'],
            ['electronicas', 'idx_elec_estado_anulado'],
            ['electronicas', 'idx_elec_fecha_entrada'],
            ['movimiento_cajas', 'idx_movcaja_tipo_fecha'],
            ['movimiento_cajas', 'idx_movcaja_estado_anulado'],
            ['facturas', 'idx_fact_tipo_fecha'],
            ['facturas', 'idx_fact_tipo_estado'],
            ['stocks', 'idx_stock_active_categoria'],
            ['abonos', 'idx_abonos_fecha'],
        ];

        foreach ($indices as [$tabla, $indice]) {
            if (Schema::hasIndex($tabla, $indice)) {
                Schema::table($tabla, fn (Blueprint $table) => $table->dropIndex($indice));
            }
        }
    }
};
