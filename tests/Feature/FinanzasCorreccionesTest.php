<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresión de los hallazgos financieros de la auditoría integral (M-01 a M-08).
 * Cada prueba reproduce el caso numérico que demostraba el defecto y exige el
 * comportamiento contable correcto.
 */
class FinanzasCorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Cliente $cliente;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->cliente = Cliente::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Ruiz']);
        $this->stock = Stock::create([
            'codigo' => 'AUD-1', 'producto' => 'Disco', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => 50, 'precio_compra' => 30000, 'utilidad' => 50,
            'precio_venta' => 50000, 'precio_tecnico' => 40000, 'active' => true,
        ]);
    }

    private function venta(int $cantidad, $precio, $pagado, ?string $fecha = null, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => $fecha ?? Carbon::today()->toDateString(),
            'total_pagado' => $pagado,
            'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $this->stock->id, 'cantidad' => $cantidad, 'precio_unitario' => $precio]],
        ] + $extra);
    }

    private function editarFactura(Factura $factura, array $datos)
    {
        $item = $factura->items()->first();

        return $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), $datos + [
            'fecha' => $factura->fecha->toDateString(),
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'existing_items' => [['id' => $item->id, 'stock_id' => $this->stock->id, 'cantidad' => $item->cantidad, 'precio_unitario' => $item->precio_unitario]],
        ]);
    }

    /** M-01: duplicar un pago no lo convierte en un segundo pago de la factura. */
    public function test_m01_duplicar_pago_no_cuenta_como_pago_de_la_factura(): void
    {
        $this->venta(2, 50000, 40000); // total 100.000, pagado 40.000
        $factura = Factura::firstOrFail();
        $base = MovimientoCaja::where('factura_id', $factura->id)->firstOrFail();

        $this->post(route('caja.duplicate', $base));
        $dup = MovimientoCaja::where('id', '!=', $base->id)->firstOrFail();
        $this->assertNull($dup->factura_id);
        $this->assertStringNotContainsString('#'.$factura->numero_factura, $dup->descripcion);

        $this->post(route('caja.abonos.store', $base), [
            'monto_abono' => 10000, 'fecha' => Carbon::today()->toDateString(), 'tipo_pago' => 'efectivo',
        ]);

        $this->assertEquals(50000, (float) $factura->fresh()->total_pagado, '40.000 + 10.000; el duplicado no es un pago');
    }

    /** M-02: un ingreso manual que menciona "#VT-n" no paga la factura. */
    public function test_m02_ingreso_manual_con_referencia_no_paga_la_factura(): void
    {
        $this->venta(2, 50000, 0);
        $factura = Factura::firstOrFail();
        $concepto = ConceptoCaja::create(['nombre' => 'Otros']);

        $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Ana Ruiz', 'fecha' => Carbon::today()->toDateString(), 'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 500000,
            'descripcion' => 'Pago cliente #'.$factura->numero_factura,
        ])->assertRedirect(route('caja.index'));

        $f = $factura->fresh();
        $f->recalcularPagos();
        $this->assertEquals(0, (float) $f->total_pagado);
        $this->assertSame('pendiente_pago', $f->estado);
    }

    /** M-03: el monto de un pago de factura no se edita desde Caja. */
    public function test_m03_editar_monto_de_pago_de_factura_se_rechaza(): void
    {
        $this->venta(2, 50000, 40000);
        $factura = Factura::firstOrFail();
        $base = MovimientoCaja::where('factura_id', $factura->id)->firstOrFail();

        $this->actingAs($this->admin)->put(route('caja.update', $base), [
            'persona' => $base->persona, 'fecha' => Carbon::today()->toDateString(), 'concepto_id' => $base->concepto_id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 300000, 'monto_total' => '',
        ])->assertSessionHasErrors('monto');

        $this->assertEquals(40000, (float) $base->fresh()->monto);
        $this->assertEquals(40000, (float) $factura->fresh()->total_pagado);
    }

    /** M-04: editar la factura no reactiva un pago anulado (reembolso) ni cambia su monto. */
    public function test_m04_editar_factura_no_reactiva_pago_anulado(): void
    {
        $this->venta(2, 50000, 100000);
        $factura = Factura::firstOrFail();
        $base = MovimientoCaja::where('factura_id', $factura->id)->firstOrFail();

        $this->post(route('caja.anular', $base), ['accion' => 'anular']);
        $this->assertTrue($base->fresh()->anulado);

        $this->editarFactura($factura->fresh(), ['total_pagado' => 0, 'observaciones' => 'nota'])->assertSessionHasNoErrors();

        $b = $base->fresh();
        $this->assertTrue((bool) $b->anulado, 'El pago anulado sigue anulado');
        $this->assertEquals(100000, (float) $b->monto, 'El monto original del reembolso se conserva');
        $this->assertEquals(0, (float) $factura->fresh()->total_pagado);
    }

    /** M-05: el pagado no se cambia editando la factura (se registra como abono con fecha real). */
    public function test_m05_editar_factura_no_reescribe_pagos_en_fechas_pasadas(): void
    {
        $hace3 = Carbon::today()->subDays(3)->toDateString();
        $this->venta(2, 50000, 0, $hace3);
        $factura = Factura::firstOrFail();

        $this->editarFactura($factura, ['total_pagado' => 100000])->assertSessionHasErrors('total_pagado');

        $this->assertEquals(0, (float) MovimientoCaja::activos()->whereDate('fecha', $hace3)->sum('monto'));
        $this->assertEquals(0, (float) $factura->fresh()->total_pagado);
    }

    /** M-06: no se registran movimientos en un día anterior a un cierre existente. */
    public function test_m06_movimiento_retroactivo_antes_de_un_cierre_se_rechaza(): void
    {
        $hoy = Carbon::today()->toDateString();
        $ayer = Carbon::yesterday()->toDateString();
        $concepto = ConceptoCaja::create(['nombre' => 'Varios']);
        $mov = fn ($fecha, $tipo, $monto) => $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'X', 'fecha' => $fecha, 'concepto_id' => $concepto->id,
            'tipo_movimiento' => $tipo, 'tipo_pago' => 'efectivo', 'monto' => $monto,
        ]);

        $mov($hoy, 'ingreso', 200000);
        $this->post(route('cierre.store'), ['fecha' => $hoy, 'efectivo_real_contado' => '200000']);
        $cierre = CierreCaja::firstOrFail();

        $mov($ayer, 'egreso', 50000);

        $this->assertSame(1, MovimientoCaja::count(), 'El egreso retroactivo no se registra');
        $this->assertEquals(200000, $cierre->fresh()->efectivo_esperado);
    }

    /** M-08: los cierres son cronológicos y nunca futuros. */
    public function test_m08_cierre_intermedio_o_futuro_se_rechaza(): void
    {
        $this->actingAs($this->admin)->post(route('cierre.store'), ['fecha' => Carbon::today()->toDateString()]);
        $this->post(route('cierre.store'), ['fecha' => Carbon::today()->subDays(2)->toDateString()])->assertSessionHasErrors('fecha');
        $this->post(route('cierre.store'), ['fecha' => Carbon::today()->addDays(10)->toDateString()])->assertSessionHasErrors('fecha');
        $this->post(route('cierre.store'), ['fecha' => 'no-es-fecha'])->assertSessionHasErrors('fecha');

        $this->assertSame(1, CierreCaja::count());
    }

    /** M-07: el mismo envío (mismo token) no se procesa dos veces. */
    public function test_m07_doble_envio_de_venta_se_procesa_una_vez(): void
    {
        $token = 'f6c0a1d2-3b4c-4d5e-8f90-a1b2c3d4e5f6';
        $this->venta(1, 50000, 50000, null, ['_idem' => $token])->assertSessionHasNoErrors();
        $this->venta(1, 50000, 50000, null, ['_idem' => $token])->assertSessionHas('warning');

        $this->assertSame(1, Factura::count());
        $this->assertEquals(50000, (float) MovimientoCaja::sum('monto'));
        $this->assertEquals(49, $this->stock->fresh()->cantidad);

        // Un envío nuevo (otro token) sí se procesa
        $this->venta(1, 50000, 50000, null, ['_idem' => 'otro-token-0000000001']);
        $this->assertSame(2, Factura::count());
    }

    /** M-07: si el primer envío falla por validación, el mismo formulario corregido se procesa. */
    public function test_m07_envio_fallido_libera_el_token(): void
    {
        $token = 'a1b2c3d4-0000-4000-8000-000000000001';
        $this->venta(1, 50000, 999999999, null, ['_idem' => $token]); // pagado > total: rechazado
        $this->assertSame(0, Factura::count());

        $this->venta(1, 50000, 50000, null, ['_idem' => $token])->assertSessionHasNoErrors();
        $this->assertSame(1, Factura::count());
    }

    /** M-07: abono de orden repetido con el mismo token; en JSON responde 409. */
    public function test_m07_doble_abono_de_orden_y_respuesta_json(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 100000, 'anulado' => false, 'estado' => 'pendiente']);
        $payload = ['monto' => 30000, 'fecha' => Carbon::today()->toDateString(), 'tipo_pago' => 'efectivo', '_idem' => 'abono-token-000000000001'];

        $this->actingAs($this->admin)->post(route('abonos.store', $orden), $payload);
        $this->postJson(route('abonos.store', $orden), $payload)->assertStatus(409)->assertJson(['duplicado' => true]);

        $this->assertSame(1, Abono::count());
        $this->assertEquals(30000, (float) $orden->fresh()->total_abonado);
    }
}
