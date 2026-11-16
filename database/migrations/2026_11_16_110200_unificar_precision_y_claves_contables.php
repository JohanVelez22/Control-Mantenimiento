<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D-11: todas las columnas de dinero pasan a DECIMAL(12,2). Las de órdenes, repuestos y
 * precios de productos eran (10,2): una orden de más de $99.999.999,99 abortaba con
 * "Out of range" y un repuesto caro no cabía en el precio del producto.
 *
 * D-12: los vínculos contables de caja (abono, factura y movimiento padre) pasan de
 * ON DELETE SET NULL a RESTRICT. Con SET NULL, un borrado físico dejaba los pagos sueltos
 * y contaban como ingresos independientes. La aplicación solo anula, así que no cambia
 * ningún flujo.
 *
 * Antes de ejecutar en producción: php artisan app:backup-db
 */
return new class extends Migration
{
    private const DINERO = [
        ['mantenimientos', 'costo', false],
        ['electronicas', 'costo', false],
        ['mantenimiento_stock', 'precio_unitario', false],
        ['stocks', 'precio_compra', false],
        ['stocks', 'precio_venta', false],
        ['stocks', 'precio_tecnico', false],
    ];

    private const VINCULOS = [
        ['abono_id', 'abonos'],
        ['factura_id', 'facturas'],
        ['parent_id', 'movimiento_cajas'],
    ];

    public function up(): void
    {
        foreach (self::DINERO as [$tabla, $columna, $nullable]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $nullable) {
                $col = $table->decimal($columna, 12, 2);
                $nullable ? $col->nullable() : $col->default(0);
                $col->change();
            });
        }

        $this->vinculos('restrict');
    }

    public function down(): void
    {
        $this->vinculos('set null');

        foreach (self::DINERO as [$tabla, $columna, $nullable]) {
            // No se puede volver a (10,2) si hay valores que no caben.
            if ((float) DB::table($tabla)->max($columna) >= 1e8) {
                throw new RuntimeException("No se puede revertir {$tabla}.{$columna} a DECIMAL(10,2): hay valores de 100 millones o más.");
            }
            Schema::table($tabla, function (Blueprint $table) use ($columna, $nullable) {
                $col = $table->decimal($columna, 10, 2);
                $nullable ? $col->nullable() : $col->default(0);
                $col->change();
            });
        }
    }

    private function vinculos(string $alBorrar): void
    {
        foreach (self::VINCULOS as [$columna, $referencia]) {
            Schema::table('movimiento_cajas', function (Blueprint $table) use ($columna, $referencia, $alBorrar) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references('id')->on($referencia)->onDelete($alBorrar);
            });
        }
    }
};
