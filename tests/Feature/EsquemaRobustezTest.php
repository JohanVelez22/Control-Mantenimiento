<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Columnas nuevas de la cuarta ronda: M-06 (foto del cierre), M-11 (costo histórico y
 * costo promedio), S-04 (código de consulta), ARQ-12 (stock mínimo) y D-21.
 */
class EsquemaRobustezTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    private function movimiento(string $fecha, string $tipo, float $monto): MovimientoCaja
    {
        return MovimientoCaja::create([
            'persona' => 'Caja', 'fecha' => $fecha, 'concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => $tipo, 'tipo_pago' => 'efectivo', 'monto' => $monto,
            'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
        ]);
    }

    // ── M-06 ────────────────────────────────────────────────────────────────────────

    public function test_cierre_guarda_la_foto_del_saldo_inicial_y_del_esperado(): void
    {
        $ayer = now()->subDay()->toDateString();
        $this->movimiento($ayer, 'ingreso', 50000);
        $this->movimiento(now()->toDateString(), 'ingreso', 20000);

        $this->actingAs($this->admin)->post(route('cierre.store'), ['fecha' => now()->toDateString(), 'efectivo_real_contado' => 70000])
            ->assertSessionHasNoErrors();

        $cierre = CierreCaja::firstOrFail();
        $this->assertEquals(50000, (float) $cierre->getAttributes()['saldo_inicial']);
        $this->assertEquals(70000, (float) $cierre->getAttributes()['efectivo_esperado']);

        // Un cambio posterior en la historia (por SQL, fuera de la aplicación) no altera el cierre ya hecho.
        DB::table('movimiento_cajas')->where('fecha', $ayer)->update(['monto' => 1]);
        $cierre = CierreCaja::firstOrFail();
        $this->assertEquals(50000, $cierre->saldo_inicial_dia);
        $this->assertEquals(70000, $cierre->efectivo_esperado);
    }

    public function test_migracion_completa_la_foto_de_los_cierres_anteriores(): void
    {
        $d1 = now()->subDays(3)->toDateString();
        $d2 = now()->subDays(2)->toDateString();
        $this->movimiento($d1, 'ingreso', 10000);
        $this->movimiento($d2, 'ingreso', 5000);
        DB::table('cierre_cajas')->insert([
            ['fecha' => $d1, 'total_ingresos' => 10000, 'total_egresos' => 0, 'efectivo' => 10000, 'efectivo_real_contado' => 9000, 'diferencia' => -1000,
                'consignacion' => 0, 'saldo_final' => 10000, 'num_movimientos' => 1, 'bloqueado' => true, 'user_id' => $this->admin->id],
            ['fecha' => $d2, 'total_ingresos' => 5000, 'total_egresos' => 0, 'efectivo' => 5000, 'efectivo_real_contado' => null, 'diferencia' => 0,
                'consignacion' => 0, 'saldo_final' => 5000, 'num_movimientos' => 1, 'bloqueado' => true, 'user_id' => $this->admin->id],
        ]);

        $migracion = require database_path('migrations/2026_11_16_110000_agregar_snapshot_cierre_y_costo_historico.php');
        $migracion->up();

        $c2 = CierreCaja::where('fecha', $d2)->firstOrFail()->getAttributes();
        // Parte de lo contado el día 1 (9.000), no de lo esperado (10.000).
        $this->assertEquals(9000, (float) $c2['saldo_inicial']);
        $this->assertEquals(14000, (float) $c2['efectivo_esperado']);
    }

    // ── M-11 ────────────────────────────────────────────────────────────────────────

    public function test_venta_guarda_el_costo_historico_y_la_utilidad_no_cambia_despues(): void
    {
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_compra' => 6000, 'precio_venta' => 10000, 'active' => true]);
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'total_pagado' => 20000, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => 10000]],
        ])->assertSessionHasNoErrors();

        $stock->update(['precio_compra' => 9000]);

        $item = FacturaItem::firstOrFail();
        $this->assertEquals(6000, (float) $item->costo_unitario);
        $this->assertEquals(8000, $item->utilidad);

        $vista = $this->get(route('reportes.financiero.acumulado'))->assertOk();
        $this->assertEquals(8000, $vista->viewData('acumulado')['utilidad_ventas']);
    }

    public function test_compra_actualiza_el_costo_promedio_ponderado(): void
    {
        $proveedor = Proveedor::create(['nombre_razon_social' => 'Mayorista', 'identificacion' => '901', 'tipo_identificacion' => 'nit', 'active' => true]);
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_compra' => 1000, 'active' => true]);

        $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$proveedor->id, 'fecha' => now()->toDateString(),
            'total_pagado' => 20000, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 10, 'precio_unitario' => 2000]],
        ])->assertSessionHasNoErrors();

        $stock->refresh();
        $this->assertSame(20, (int) $stock->cantidad);
        $this->assertEquals(1500, (float) $stock->precio_compra);
        $this->assertEquals(2000, (float) FacturaItem::firstOrFail()->costo_unitario);
    }

    // ── S-04 ────────────────────────────────────────────────────────────────────────

    public function test_cada_orden_recibe_un_codigo_de_consulta_distinto(): void
    {
        $a = Mantenimiento::factory()->create();
        $b = Mantenimiento::factory()->create();

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $a->codigo_consulta);
        $this->assertNotSame($a->codigo_consulta, $b->codigo_consulta);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $a->codigo_consulta_formateado);
    }

    public function test_invitado_no_puede_consultar_con_el_numero_de_orden_secuencial(): void
    {
        $invitado = User::factory()->create(['role' => 'invitado', 'active' => true, 'must_change_password' => false]);
        $orden = Mantenimiento::factory()->create(['anulado' => false, 'descripcion' => 'Diagnóstico privado']);
        $cedula = $orden->equipo->cliente->identificacion;

        // Con el número de orden (enumerable) ya no basta.
        $this->actingAs($invitado)->get(route('guest.search', ['tipo' => 'mantenimiento', 'identificacion' => $cedula, 'id_orden' => $orden->id_orden]))
            ->assertSessionHasErrors('codigo');

        // Con el código (en minúsculas y con guion) sí.
        $this->get(route('guest.search', ['tipo' => 'mantenimiento', 'identificacion' => $cedula, 'codigo' => strtolower($orden->codigo_consulta_formateado)]))
            ->assertOk()->assertSee('Diagnóstico privado');
    }

    public function test_comprobante_de_recepcion_se_genera_para_una_orden_pendiente(): void
    {
        $orden = Mantenimiento::factory()->create(['anulado' => false, 'estado' => 'pendiente', 'fecha_salida' => null]);

        $respuesta = $this->actingAs($this->admin)->get(route('mantenimientos.recepcion', $orden))->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
    }

    // ── ARQ-12 ──────────────────────────────────────────────────────────────────────

    public function test_stock_minimo_propio_define_el_stock_bajo(): void
    {
        Stock::factory()->create(['cantidad' => 10, 'stock_minimo' => 20, 'active' => true]);
        Stock::factory()->create(['cantidad' => 10, 'stock_minimo' => null, 'active' => true]);

        $this->assertSame(1, Stock::activos()->bajoMinimo()->count());
    }

    // ── D-11 / D-21 ─────────────────────────────────────────────────────────────────

    public function test_costo_de_orden_admite_mas_de_cien_millones(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 150000000.50]);

        $this->assertEquals(150000000.50, (float) $orden->fresh()->costo);
    }

    public function test_revertir_proveedores_se_detiene_si_hay_equipos_de_proveedor(): void
    {
        $proveedor = Proveedor::create(['nombre_razon_social' => 'Prov', 'identificacion' => '902', 'tipo_identificacion' => 'nit', 'active' => true]);
        Equipo::factory()->create(['cliente_id' => null, 'proveedor_id' => $proveedor->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('registros de proveedor');
        (require database_path('migrations/2026_09_09_200000_add_proveedor_id_to_cotizaciones_and_equipos_tables.php'))->down();
    }
}
