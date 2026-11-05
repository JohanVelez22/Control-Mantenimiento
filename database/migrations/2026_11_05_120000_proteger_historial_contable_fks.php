<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protege el historial contable: las claves foráneas pasan de ON DELETE CASCADE a RESTRICT.
 *
 * Con CASCADE, borrar físicamente un usuario, cliente, equipo, técnico, producto, orden,
 * factura o cotización (por SQL o tinker) eliminaba en silencio sus órdenes, abonos,
 * movimientos de caja y cierres. La aplicación solo anula o desactiva registros, así que
 * RESTRICT no cambia ningún flujo: únicamente impide esos borrados a nivel de base de datos.
 *
 * Antes de ejecutar en producción: php artisan app:backup-db
 */
return new class extends Migration
{
    /** [tabla, columna, tabla referenciada] */
    private const CLAVES = [
        ['abonos', 'mantenimiento_id', 'mantenimientos'],
        ['abonos', 'electronica_id', 'electronicas'],
        ['abonos', 'user_id', 'users'],
        ['bajas_stock', 'stock_id', 'stocks'],
        ['cierre_cajas', 'user_id', 'users'],
        ['cotizacion_items', 'cotizacion_id', 'cotizaciones'],
        ['electronica_stock', 'electronica_id', 'electronicas'],
        ['electronica_stock', 'stock_id', 'stocks'],
        ['electronicas', 'equipo_id', 'equipos'],
        ['electronicas', 'tecnico_id', 'tecnicos'],
        ['electronicas', 'user_id', 'users'],
        ['equipos', 'cliente_id', 'clientes'],
        ['equipos', 'user_id', 'users'],
        ['factura_items', 'factura_id', 'facturas'],
        ['mantenimiento_stock', 'mantenimiento_id', 'mantenimientos'],
        ['mantenimiento_stock', 'stock_id', 'stocks'],
        ['mantenimientos', 'equipo_id', 'equipos'],
        ['mantenimientos', 'tecnico_id', 'tecnicos'],
        ['mantenimientos', 'user_id', 'users'],
        ['movimiento_cajas', 'user_id', 'users'],
    ];

    public function up(): void
    {
        $this->cambiar('restrict');
    }

    public function down(): void
    {
        $this->cambiar('cascade');
    }

    private function cambiar(string $alBorrar): void
    {
        foreach (self::CLAVES as [$tabla, $columna, $referencia]) {
            if (! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) use ($columna, $referencia, $alBorrar) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references('id')->on($referencia)->onDelete($alBorrar);
            });
        }
    }
};
