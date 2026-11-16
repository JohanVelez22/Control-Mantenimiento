<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use App\Support\Esquema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El código se publica antes de que el dueño ejecute `php artisan migrate --force`.
 * Esta prueba revierte las migraciones de la cuarta ronda y recorre los flujos
 * principales: nada debe fallar por las columnas que todavía no existen.
 */
class CompatibilidadSinMigrarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'sqlite') {
            // En MySQL el DDL confirma la transacción de la prueba y dejaría la base sin columnas.
            $this->markTestSkipped('Solo en SQLite (el DDL de MySQL no se puede revertir dentro de la prueba).');
        }
        // Se quitan las columnas que agregan las migraciones 2026_11_16_*. (Revertirlas con
        // migrate:rollback reconstruye tablas en SQLite y choca con las columnas generadas
        // de facturas; los cambios de índices, precisión y llaves no afectan al código.)
        foreach (['mantenimientos', 'electronicas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropUnique(['codigo_consulta']);
            });
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn('codigo_consulta'));
        }
        Schema::table('cierre_cajas', fn (Blueprint $t) => $t->dropColumn(['saldo_inicial', 'efectivo_esperado']));
        Schema::table('factura_items', fn (Blueprint $t) => $t->dropColumn('costo_unitario'));
        Schema::table('stocks', fn (Blueprint $t) => $t->dropColumn('stock_minimo'));
        Esquema::olvidar();
    }

    protected function tearDown(): void
    {
        Esquema::olvidar();
        parent::tearDown();
    }

    public function test_flujos_principales_funcionan_sin_las_columnas_nuevas(): void
    {
        $this->assertFalse(Schema::hasColumn('cierre_cajas', 'saldo_inicial'));
        $this->assertFalse(Schema::hasColumn('mantenimientos', 'codigo_consulta'));
        $this->assertFalse(Schema::hasColumn('factura_items', 'costo_unitario'));
        $this->assertFalse(Schema::hasColumn('stocks', 'stock_minimo'));

        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($admin);

        // Orden + abono + recepción
        $orden = Mantenimiento::factory()->create(['costo' => 100000, 'anulado' => false, 'estado' => 'pendiente', 'fecha_entrada' => now()->toDateString(), 'fecha_salida' => null]);
        $this->assertNull($orden->codigo_consulta_formateado);
        $this->post(route('abonos.store', $orden), ['monto' => 20000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo'])->assertSessionHasNoErrors();
        $this->get(route('mantenimientos.show', $orden))->assertOk();
        $this->get(route('mantenimientos.recepcion', $orden))->assertOk();

        // Venta y producto
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_compra' => 5000, 'precio_venta' => 8000, 'active' => true]);
        $this->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'total_pagado' => 8000, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 8000]],
        ])->assertSessionHasNoErrors();
        $this->assertNull(FacturaItem::firstOrFail()->getAttributes()['costo_unitario'] ?? null);
        $this->get(route('stocks.edit', $stock))->assertOk()->assertDontSee('name="stock_minimo"', false);
        $this->assertSame(0, Stock::activos()->bajoMinimo()->count());

        // Caja y cierre
        MovimientoCaja::create([
            'persona' => 'Caja', 'fecha' => now()->toDateString(), 'concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000, 'estado' => 'activo', 'anulado' => false, 'user_id' => $admin->id,
        ]);
        $this->post(route('cierre.store'), ['fecha' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->assertEquals(29000, CierreCaja::firstOrFail()->efectivo_esperado);

        // Pantallas
        foreach (['dashboard', 'caja.index', 'cierre.index', 'reportes.financiero.acumulado', 'mantenimientos.reportes', 'stocks.index'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }

        // Consulta del invitado: sigue con número de orden mientras no exista el código.
        $invitado = User::factory()->create(['role' => 'invitado', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($invitado)->get(route('guest.search', [
            'tipo' => 'mantenimiento', 'identificacion' => $orden->equipo->cliente->identificacion, 'id_orden' => $orden->id_orden,
        ]))->assertOk()->assertSessionHasNoErrors();
    }
}
