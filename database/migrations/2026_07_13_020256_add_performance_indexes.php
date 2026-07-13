<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Facturas: queries frecuentes por estado, tipo, facturable, fechas
        Schema::table('facturas', function (Blueprint $table) {
            $table->index(['estado', 'tipo_movimiento'], 'idx_facturas_estado_tipo');
            // idx_facturas_facturable already exists from audit migration
            $table->index(['fecha', 'estado'], 'idx_facturas_fecha_estado');
            $table->index('saldo_pendiente', 'idx_facturas_saldo_pendiente');
        });

        // FacturaItems: queries por factura - these already exist as FK constraints
        // Schema::table('factura_items', function (Blueprint $table) {
        //     $table->index('factura_id', 'idx_facturaitems_factura');
        //     $table->index('stock_id', 'idx_facturaitems_stock');
        // });

        // Stocks: queries por proveedor, categoria, activos, bajo stock
        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['active', 'cantidad'], 'idx_stocks_active_cantidad');
            // idx_stocks_proveedor_active already exists as FK constraint
            $table->index('categoria', 'idx_stocks_categoria');
        });

        // Mantenimientos: queries por estado, equipo, tecnico, fechas
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->index(['anulado', 'estado'], 'idx_mant_anulado_estado');
            $table->index(['equipo_id', 'anulado'], 'idx_mant_equipo_anulado');
            $table->index(['tecnico_id', 'anulado'], 'idx_mant_tecnico_anulado');
            $table->index(['fecha_entrada', 'anulado'], 'idx_mant_fecha_anulado');
        });

        // Electronicas: queries similares
        Schema::table('electronicas', function (Blueprint $table) {
            $table->index(['anulado', 'estado'], 'idx_elec_anulado_estado');
            $table->index(['equipo_id', 'anulado'], 'idx_elec_equipo_anulado');
            $table->index(['tecnico_id', 'anulado'], 'idx_elec_tecnico_anulado');
            $table->index(['fecha_entrada', 'anulado'], 'idx_elec_fecha_anulado');
        });

        // Equipos: queries por cliente
        Schema::table('equipos', function (Blueprint $table) {
            $table->index(['cliente_id', 'active'], 'idx_equipos_cliente_activo');
        });

// Clientes/Proveedores: queries por activo
        Schema::table('clientes', function (Blueprint $table) {
            $table->index('active', 'idx_clientes_active');
        });
        Schema::table('proveedores', function (Blueprint $table) {
            $table->index('active', 'idx_proveedores_active');
        });
        Schema::table('proveedores', function (Blueprint $table) {
            $table->index('activo', 'idx_proveedores_activo');
        });

        // MovimientoCaja: parent_id for abonos
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->index('parent_id', 'idx_movcaja_parent');
            // idx_movcaja_tipo_fecha already exists from audit migration
            $table->index(['concepto_id', 'fecha'], 'idx_movcaja_concepto_fecha');
        });

        // Cierres de caja
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->index('fecha', 'idx_cierrecaja_fecha');
        });

        // Abonos: already indexed but add composite
        Schema::table('abonos', function (Blueprint $table) {
            $table->index(['mantenimiento_id', 'fecha'], 'idx_abonos_mant_fecha');
            $table->index(['electronica_id', 'fecha'], 'idx_abonos_elec_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex('idx_facturas_facturable');
            $table->dropIndex('idx_facturas_fecha_estado');
            $table->dropIndex('idx_facturas_saldo_pendiente');
        });
        Schema::table('factura_items', function (Blueprint $table) {
            // $table->dropIndex('idx_facturaitems_factura');
            // $table->dropIndex('idx_facturaitems_stock');
        });
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('idx_stocks_active_cantidad');
            // $table->dropIndex('idx_stocks_proveedor_active');
            $table->dropIndex('idx_stocks_categoria');
        });
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropIndex('idx_mant_anulado_estado');
            $table->dropIndex('idx_mant_equipo_anulado');
            $table->dropIndex('idx_mant_tecnico_anulado');
            $table->dropIndex('idx_mant_fecha_anulado');
        });
        Schema::table('electronicas', function (Blueprint $table) {
            $table->dropIndex('idx_elec_anulado_estado');
            $table->dropIndex('idx_elec_equipo_anulado');
            $table->dropIndex('idx_elec_tecnico_anulado');
            $table->dropIndex('idx_elec_fecha_anulado');
        });
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex('idx_equipos_cliente_active');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('idx_clientes_active');
        });
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropIndex('idx_proveedores_active');
        });
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropIndex('idx_movcaja_parent');
            $table->dropIndex('idx_movcaja_concepto_fecha');
        });
        Schema::table('cierre_cajas', function (Blueprint $table) {
            $table->dropIndex('idx_cierrecaja_fecha');
        });
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropIndex('idx_abonos_mant_fecha');
            $table->dropIndex('idx_abonos_elec_fecha');
        });
    }
};