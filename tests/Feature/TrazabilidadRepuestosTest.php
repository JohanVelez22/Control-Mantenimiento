<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B-04 / M-17 / M-18 / B-23: trazabilidad de los repuestos de una orden.
 */
class TrazabilidadRepuestosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    private function orden(): Mantenimiento
    {
        return Mantenimiento::factory()->create([
            'costo' => 0, 'anulado' => false, 'estado' => 'pendiente', 'fecha_salida' => null,
        ]);
    }

    public function test_agregar_y_quitar_repuesto_queda_en_el_historial_con_producto_y_cantidad(): void
    {
        $orden = $this->orden();
        $stock = Stock::factory()->create(['producto' => 'Disco SSD 480', 'cantidad' => 10, 'precio_venta' => 150000, 'active' => true]);

        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 2]);
        $agregado = Evento::where('accion', 'repuesto_agregado')->where('modelo_id', $orden->id)->firstOrFail();
        $this->assertSame('Disco SSD 480', $agregado->valores_nuevos['producto']);
        $this->assertSame(2, $agregado->valores_nuevos['cantidad']);
        $this->assertStringContainsString($orden->id_orden, $agregado->descripcion);

        $this->delete(route('mantenimientos.stocks.destroy', [$orden, $stock->id]));
        $quitado = Evento::where('accion', 'repuesto_quitado')->where('modelo_id', $orden->id)->firstOrFail();
        $this->assertSame(2, $quitado->valores_antiguos['cantidad']);
    }

    public function test_descripcion_del_historial_nombra_la_orden(): void
    {
        $orden = $this->orden();
        $orden->update(['descripcion' => 'Cambio de pantalla']);

        $evento = Evento::where('accion', 'actualizado')->where('modelo_id', $orden->id)->latest('id')->firstOrFail();
        $this->assertStringContainsString($orden->id_orden, $evento->descripcion);
    }

    public function test_precio_promedio_no_deja_el_costo_descuadrado_ni_negativo(): void
    {
        $orden = $this->orden();
        // Mismo precio para cliente normal y técnico: el tipo de cliente de la fábrica es aleatorio.
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_venta' => 10000, 'precio_tecnico' => 10000, 'active' => true]);

        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 1]);
        $stock->update(['precio_venta' => 10001, 'precio_tecnico' => 10001]);
        $this->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 2]);

        $orden->refresh();
        $this->assertEquals($orden->valorRepuestos(), (float) $orden->costo, 'El costo debe ser igual al valor de los repuestos');

        $this->delete(route('mantenimientos.stocks.destroy', [$orden, $stock->id]));
        $this->assertEquals(0.0, (float) $orden->fresh()->costo);
    }

    public function test_ficha_de_la_orden_muestra_el_historial_solo_al_administrador(): void
    {
        $orden = $this->orden();
        $stock = Stock::factory()->create(['producto' => 'Memoria RAM 8GB', 'cantidad' => 10, 'precio_venta' => 90000, 'active' => true]);
        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 1]);

        $this->get(route('mantenimientos.show', $orden))->assertOk()
            ->assertSee('Historial de la orden')->assertSee('Repuesto Memoria RAM 8GB');

        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($tecnico)->get(route('mantenimientos.show', $orden))->assertOk()->assertDontSee('Historial de la orden');
    }

    public function test_kardex_del_producto_incluye_su_uso_como_repuesto(): void
    {
        $orden = $this->orden();
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_venta' => 20000, 'active' => true]);
        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 4]);

        $vista = $this->get(route('stocks.show', $stock))->assertOk()->assertSee('Uso como repuesto en órdenes')->assertSee($orden->id_orden);
        $this->assertSame(4, $vista->viewData('repuestos')->sum('cantidad'));
    }
}
