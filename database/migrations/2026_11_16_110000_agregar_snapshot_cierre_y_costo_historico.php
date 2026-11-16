<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M-06 y M-11: "fotos" que no deben cambiar con el tiempo.
 *
 * - cierre_cajas.saldo_inicial y efectivo_esperado: el efectivo con que arrancó el día y
 *   el que debía haber al contar. Antes se recalculaban al mostrar el cierre, recorriendo
 *   toda la caja anterior.
 * - factura_items.costo_unitario: el costo del producto al momento de la venta o compra,
 *   para que la utilidad de una venta no cambie si después cambia el precio de compra.
 *
 * Los cierres existentes se completan en orden cronológico con la misma regla que usa la
 * aplicación. Los ítems existentes toman el precio de compra actual del producto (es la
 * mejor aproximación disponible; queda anotado en eventos). Reversible: down() elimina
 * las columnas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cierre_cajas', 'saldo_inicial')) {
            Schema::table('cierre_cajas', function (Blueprint $table) {
                $table->decimal('saldo_inicial', 12, 2)->nullable()->after('consignacion');
                $table->decimal('efectivo_esperado', 12, 2)->nullable()->after('saldo_inicial');
            });
        }
        if (! Schema::hasColumn('factura_items', 'costo_unitario')) {
            Schema::table('factura_items', function (Blueprint $table) {
                $table->decimal('costo_unitario', 12, 2)->nullable()->after('precio_unitario');
            });
        }

        $this->completarCierres();
        $this->completarCostos();
    }

    public function down(): void
    {
        if (Schema::hasColumn('cierre_cajas', 'saldo_inicial')) {
            Schema::table('cierre_cajas', function (Blueprint $table) {
                $table->dropColumn(['saldo_inicial', 'efectivo_esperado']);
            });
        }
        if (Schema::hasColumn('factura_items', 'costo_unitario')) {
            Schema::table('factura_items', function (Blueprint $table) {
                $table->dropColumn('costo_unitario');
            });
        }
    }

    /** Misma regla que CierreCaja::saldoInicialEfectivo(): último conteo real + flujo intermedio. */
    private function completarCierres(): void
    {
        $flujo = function (?string $desdeExcluido, string $hastaExcluido): float {
            $q = DB::table('movimiento_cajas')
                ->where('estado', 'activo')->where('anulado', false)->where('tipo_pago', 'efectivo')
                ->where('fecha', '<', $hastaExcluido);
            if ($desdeExcluido) {
                // Desde el día siguiente (comparar con '>' la fecha sola falla en SQLite, que
                // guarda la hora junto a la fecha).
                $q->where('fecha', '>=', date('Y-m-d', strtotime($desdeExcluido.' +1 day')));
            }

            return (float) ($q->selectRaw("SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE -monto END) as total")->value('total') ?? 0);
        };

        $ultimoConteo = null; // [fecha, contado]
        foreach (DB::table('cierre_cajas')->orderBy('fecha')->get(['id', 'fecha', 'efectivo', 'efectivo_real_contado', 'saldo_inicial']) as $cierre) {
            $fecha = substr((string) $cierre->fecha, 0, 10);
            $saldoInicial = $ultimoConteo
                ? round($ultimoConteo[1] + $flujo($ultimoConteo[0], $fecha), 2)
                : round($flujo(null, $fecha), 2);

            if ($cierre->saldo_inicial === null) {
                DB::table('cierre_cajas')->where('id', $cierre->id)->update([
                    'saldo_inicial' => $saldoInicial,
                    'efectivo_esperado' => round($saldoInicial + (float) $cierre->efectivo, 2),
                ]);
            }

            if ($cierre->efectivo_real_contado !== null) {
                $ultimoConteo = [$fecha, (float) $cierre->efectivo_real_contado];
            }
        }
    }

    private function completarCostos(): void
    {
        $sinCosto = DB::table('factura_items')->whereNull('costo_unitario')->whereNotNull('stock_id')->count();
        if ($sinCosto === 0) {
            return;
        }

        DB::table('factura_items')
            ->whereNull('costo_unitario')
            ->whereNotNull('stock_id')
            ->update(['costo_unitario' => DB::raw('(SELECT s.precio_compra FROM stocks s WHERE s.id = factura_items.stock_id)')]);

        DB::table('eventos')->insert([
            'user_id' => null,
            'accion' => 'editar',
            'modelo_tipo' => 'FacturaItem',
            'modelo_id' => null,
            'descripcion' => "Migración: {$sinCosto} ítem(s) de facturas anteriores tomaron como costo histórico el precio de compra vigente al migrar (no se conocía el de la fecha de venta).",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
