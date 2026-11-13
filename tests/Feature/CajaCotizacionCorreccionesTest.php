<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M-14, M-19, M-20, M-21, M-22 y M-24 de la auditoría financiera.
 */
class CajaCotizacionCorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    private function stock(int $cantidad = 10): Stock
    {
        return Stock::create([
            'codigo' => 'R-1', 'producto' => 'Disco SSD', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => $cantidad, 'precio_compra' => 50000, 'utilidad' => 40, 'precio_venta' => 70000, 'active' => true,
        ]);
    }

    private function cierre(string $fecha, float $diferencia = 0, ?string $motivo = null): CierreCaja
    {
        return CierreCaja::create([
            'fecha' => $fecha, 'total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0,
            'consignacion' => 0, 'saldo_final' => 0, 'num_movimientos' => 0, 'bloqueado' => true,
            'diferencia' => $diferencia, 'motivo_diferencia' => $motivo, 'user_id' => $this->admin->id,
        ]);
    }

    public function test_m14_el_motivo_de_un_descuadre_no_se_puede_borrar(): void
    {
        $cierre = $this->cierre(now()->subDay()->toDateString(), 5000, 'Billete roto');

        $this->actingAs($this->admin)->put(route('cierre.update', $cierre), ['motivo_diferencia' => '', 'observaciones' => 'x'])
            ->assertSessionHasErrors('motivo_diferencia');
        $this->assertSame('Billete roto', $cierre->fresh()->motivo_diferencia);

        $this->put(route('cierre.update', $cierre), ['motivo_diferencia' => 'Billete roto, devuelto al banco'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Billete roto, devuelto al banco', $cierre->fresh()->motivo_diferencia);
    }

    public function test_m14_cierre_cuadrado_puede_quedar_sin_motivo(): void
    {
        $cierre = $this->cierre(now()->subDay()->toDateString());

        $this->actingAs($this->admin)->put(route('cierre.update', $cierre), ['motivo_diferencia' => '', 'observaciones' => 'Todo bien'])
            ->assertSessionHasNoErrors();
    }

    public function test_m19_no_se_acepta_un_centavo_de_mas_en_un_abono(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 100000, 'anulado' => false, 'estado' => 'pendiente']);

        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => '100000.01', 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ]);

        $this->assertEquals(0, $orden->fresh()->total_abonado);

        $this->post(route('abonos.store', $orden), [
            'monto' => '100000', 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ]);
        $this->assertEquals(100000, $orden->fresh()->total_abonado);
    }

    public function test_m19_comparacion_en_centavos(): void
    {
        $this->assertTrue(Factura::superaMonto(100000.01, 100000));
        $this->assertFalse(Factura::superaMonto(100000, 100000));
        $this->assertFalse(Factura::superaMonto(0.1 + 0.2, 0.3));
        $this->assertFalse(Factura::superaMonto('99999.999', 100000));
    }

    public function test_m20_venta_en_cero_en_dia_cerrado_se_rechaza(): void
    {
        $stock = $this->stock();
        $cliente = Cliente::factory()->create();
        $ayer = now()->subDay()->toDateString();
        $this->cierre($ayer);

        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$cliente->id, 'fecha' => $ayer, 'total_pagado' => 0, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 3, 'precio_unitario' => 0]],
        ])->assertSessionHas('error');

        $this->assertSame(0, Factura::count());
        $this->assertSame(10, (int) $stock->fresh()->cantidad);
    }

    public function test_m21_doble_envio_de_anular_no_reactiva_la_cotizacion(): void
    {
        $cot = Cotizacion::create([
            'codigo' => 'COT-1', 'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'validez_dias' => 15, 'total' => 1000, 'estado' => 'pendiente', 'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('cotizaciones.anular', $cot), ['accion' => 'anular']);
        $this->assertTrue((bool) $cot->fresh()->anulado);

        $this->post(route('cotizaciones.anular', $cot), ['accion' => 'anular'])->assertSessionHas('error');
        $this->assertTrue((bool) $cot->fresh()->anulado);
    }

    public function test_m22_cotizacion_vencida_no_se_convierte(): void
    {
        $stock = $this->stock();
        $cot = Cotizacion::create([
            'codigo' => 'COT-2', 'cliente_id' => Cliente::factory()->create()->id,
            'fecha' => now()->subDays(120)->toDateString(), 'validez_dias' => 15,
            'total' => 50000, 'estado' => 'pendiente', 'user_id' => $this->admin->id,
        ]);
        CotizacionItem::create([
            'cotizacion_id' => $cot->id, 'tipo' => 'stock', 'item_id' => $stock->id,
            'descripcion' => 'Disco SSD', 'cantidad' => 1, 'precio_unitario' => 50000, 'subtotal' => 50000,
        ]);

        $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cot))->assertSessionHas('error');

        $this->assertSame('pendiente', $cot->fresh()->estado);
        $this->assertSame(0, Factura::count());
        $this->assertSame(10, (int) $stock->fresh()->cantidad);
    }

    public function test_m24_costo_de_orden_tiene_tope_al_crear(): void
    {
        $plantilla = Mantenimiento::factory()->make();

        $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(), 'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'descripcion' => 'Costo enorme', 'costo' => '1000000000', 'estado' => 'pendiente',
            'equipo_id' => $plantilla->equipo_id, 'tecnico_id' => $plantilla->tecnico_id,
        ])->assertSessionHasErrors('costo');
    }
}
