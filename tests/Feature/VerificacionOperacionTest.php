<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Configuracion;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hallazgos del recorrido de punta a punta por el navegador (copia aislada con MySQL, 16/11/2026).
 * Cada prueba reproduce lo que se observó en la pantalla antes de la corrección.
 */
class VerificacionOperacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
        $this->proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '900123456',
            'nombre_razon_social' => 'Distribuidora Prueba', 'active' => true,
        ]);
    }

    private function producto(array $extra = []): array
    {
        return $extra + [
            'codigo' => 'P-'.uniqid(), 'producto' => 'Mouse USB', 'categoria' => 'Accesorios', 'subcategoria' => 'Repuestos',
            'cantidad' => 10, 'proveedor_id' => $this->proveedor->id, 'precio_compra' => 10000, 'utilidad' => 50,
        ];
    }

    private function stock(float $costo = 80000, int $cantidad = 10): Stock
    {
        return Stock::create([
            'codigo' => 'S-'.uniqid(), 'producto' => 'Memoria RAM', 'categoria' => 'Tecnologia', 'subcategoria' => 'Repuestos',
            'cantidad' => $cantidad, 'precio_compra' => $costo, 'utilidad' => 30, 'precio_venta' => $costo * 1.3,
            'precio_tecnico' => $costo * 1.15, 'proveedor_id' => $this->proveedor->id, 'active' => true,
        ]);
    }

    private function movimientoManual(array $datos)
    {
        return $this->actingAs($this->admin)->post(route('caja.store'), $datos + [
            'persona' => 'Proveedor varios', 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
            'tipo_movimiento' => 'egreso', 'monto' => 40000, 'monto_total' => 40000,
            'concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Gastos varios'])->id,
        ]);
    }

    // ── Inventario ──────────────────────────────────────────────────────────

    public function test_el_precio_de_venta_de_un_producto_no_puede_quedar_por_debajo_del_costo(): void
    {
        $this->actingAs($this->admin)->post(route('stocks.store'), $this->producto(['precio_venta' => 1]))
            ->assertSessionHasErrors('precio_venta');
        $this->actingAs($this->admin)->post(route('stocks.store'), $this->producto(['precio_venta' => 15000, 'precio_tecnico' => 9000]))
            ->assertSessionHasErrors('precio_venta');
        $this->assertSame(0, Stock::count());
    }

    public function test_los_precios_vacios_se_calculan_con_la_utilidad(): void
    {
        $this->actingAs($this->admin)->post(route('stocks.store'), $this->producto())->assertSessionHasNoErrors();

        $stock = Stock::firstOrFail();
        $this->assertEquals(15000, (float) $stock->precio_venta, 'costo + 50 %');
        $this->assertEquals(12500, (float) $stock->precio_tecnico, 'costo + la mitad de la utilidad');
    }

    public function test_editar_un_producto_tambien_valida_el_precio_contra_el_costo(): void
    {
        $stock = $this->stock();
        $this->actingAs($this->admin)->put(route('stocks.update', $stock), $this->producto([
            'codigo' => $stock->codigo, 'cantidad' => $stock->cantidad, 'precio_compra' => 80000, 'precio_venta' => 50000,
        ]))->assertSessionHasErrors('precio_venta');
        $this->assertEquals(104000, (float) $stock->fresh()->precio_venta);
    }

    // ── Utilidad de las ventas ──────────────────────────────────────────────

    public function test_la_utilidad_de_una_venta_no_cambia_cuando_luego_cambia_el_costo(): void
    {
        $stock = $this->stock(120000, 3);
        $cliente = Cliente::factory()->create();
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$cliente->id, 'fecha' => now()->toDateString(),
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 150000]],
            'total_pagado' => 150000, 'tipo_pago' => 'efectivo',
        ])->assertSessionHasNoErrors();
        $venta = Factura::where('tipo_movimiento', 'venta')->firstOrFail();
        $this->assertEquals(30000, $venta->utilidad);

        // Una compra a otro precio cambia el costo promedio del producto…
        $this->registrarEfectivoEnCaja();
        $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id, 'fecha' => now()->toDateString(),
            'items' => [['stock_id' => $stock->id, 'cantidad' => 6, 'precio_unitario' => 100000]],
            'total_pagado' => 600000, 'tipo_pago' => 'efectivo',
        ])->assertSessionHasNoErrors();
        $this->assertNotEquals(120000, (float) $stock->fresh()->precio_compra);

        // …pero la utilidad de la venta ya hecha se queda con el costo de ese momento.
        $this->assertEquals(30000, Factura::findOrFail($venta->id)->utilidad);
    }

    public function test_cambiar_el_producto_de_un_item_actualiza_su_costo_guardado(): void
    {
        $barato = $this->stock(10000);
        $caro = $this->stock(90000);
        $factura = Factura::create([
            'numero_factura' => 'VT-90', 'tipo_movimiento' => 'venta', 'estado' => 'emitida',
            'facturable_id' => Cliente::factory()->create()->id, 'facturable_type' => Cliente::class,
            'total_documento' => 100000, 'total_pagado' => 100000, 'fecha' => now()->toDateString(), 'user_id' => $this->admin->id,
        ]);
        $item = FacturaItem::create(['factura_id' => $factura->id, 'stock_id' => $barato->id, 'cantidad' => 1, 'precio_unitario' => 100000]);
        $this->assertEquals(10000, (float) $item->costo_unitario);

        $item->update(['stock_id' => $caro->id]);
        $this->assertEquals(90000, (float) $item->fresh()->costo_unitario);
    }

    // ── Caja ────────────────────────────────────────────────────────────────

    public function test_una_salida_en_efectivo_no_puede_dejar_la_caja_en_negativo(): void
    {
        $this->movimientoManual(['monto' => 99999999, 'monto_total' => 99999999])->assertSessionHasErrors('monto');
        $this->assertSame(0, MovimientoCaja::count());

        $this->registrarEfectivoEnCaja(50000);
        $this->movimientoManual(['monto' => 40000])->assertSessionHasNoErrors();
        $this->movimientoManual(['monto' => 20000])->assertSessionHasErrors('monto'); // quedan 10.000

        // Por consignación no se toca el efectivo del cajón.
        $this->movimientoManual(['monto' => 20000, 'monto_total' => 20000, 'tipo_pago' => 'consignacion'])->assertSessionHasNoErrors();
    }

    public function test_editar_un_egreso_cuenta_lo_que_ese_mismo_egreso_ya_restaba(): void
    {
        $this->registrarEfectivoEnCaja(50000);
        $this->movimientoManual(['monto' => 50000, 'monto_total' => 50000])->assertSessionHasNoErrors();
        $mov = MovimientoCaja::where('tipo_movimiento', 'egreso')->firstOrFail();

        $datos = [
            'persona' => 'Proveedor varios', 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
            'tipo_movimiento' => 'egreso', 'concepto_id' => $mov->concepto_id, 'descripcion' => 'corregido',
        ];
        $this->actingAs($this->admin)->put(route('caja.update', $mov), $datos + ['monto' => 50000, 'monto_total' => 50000])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->put(route('caja.update', $mov), $datos + ['monto' => 60000, 'monto_total' => 60000])->assertSessionHasErrors('monto');
    }

    public function test_una_compra_en_efectivo_sin_dinero_en_caja_se_rechaza(): void
    {
        $stock = $this->stock();
        $compra = fn (string $medio) => $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id, 'fecha' => now()->toDateString(),
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 80000]],
            'total_pagado' => 80000, 'tipo_pago' => $medio,
        ]);

        $compra('efectivo')->assertSessionHas('error');
        $this->assertSame(0, Factura::count());
        $this->assertSame(10, $stock->fresh()->cantidad);

        $compra('consignacion')->assertSessionHas('success');
        $this->assertSame(11, $stock->fresh()->cantidad);
    }

    public function test_no_se_registran_movimientos_con_fecha_futura(): void
    {
        $this->registrarEfectivoEnCaja();
        $this->movimientoManual(['fecha' => now()->addDays(10)->toDateString(), 'tipo_movimiento' => 'ingreso'])
            ->assertSessionHasErrors('fecha');

        $stock = $this->stock();
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::factory()->create()->id, 'fecha' => now()->addDay()->toDateString(),
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 104000]],
            'total_pagado' => 104000, 'tipo_pago' => 'efectivo',
        ])->assertSessionHasErrors('fecha');
    }

    public function test_un_movimiento_manual_no_usa_conceptos_del_sistema(): void
    {
        $this->registrarEfectivoEnCaja();
        $sistema = ConceptoCaja::firstOrCreate(['nombre' => 'Abono Mantenimiento']);
        $this->movimientoManual(['tipo_movimiento' => 'ingreso', 'concepto_id' => $sistema->id])->assertSessionHasErrors('concepto_id');
        $this->movimientoManual(['tipo_movimiento' => 'ingreso', 'nuevo_concepto' => 'Venta de Inventario', 'concepto_id' => null])
            ->assertSessionHasErrors('concepto_id');

        $this->actingAs($this->admin)->get(route('caja.create'))
            ->assertOk()
            ->assertDontSee('>Abono Mantenimiento<', false)
            ->assertSee('Gastos varios');
    }

    // ── Documentos ──────────────────────────────────────────────────────────

    public function test_la_orden_impresa_muestra_el_valor_del_servicio_ademas_de_los_repuestos(): void
    {
        $tecnico = Tecnico::create(['nombre' => 'Andrés', 'identificacion' => '1088', 'movil' => '3101112233', 'especialidad' => 'Hardware', 'active' => true]);
        $equipo = Equipo::create([
            'cliente_id' => Cliente::factory()->create()->id, 'nombre' => 'Portátil', 'marca' => 'Lenovo',
            'modelo' => 'IdeaPad', 'serie' => 'SN-1', 'active' => true, 'user_id' => $this->admin->id,
        ]);
        $orden = Mantenimiento::create([
            'id_orden' => 'ORD-1', 'equipo_id' => $equipo->id, 'tecnico_id' => $tecnico->id, 'user_id' => $this->admin->id,
            'fecha_entrada' => now()->toDateString(), 'fecha_salida' => now()->toDateString(), 'descripcion' => 'No enciende',
            'tipo' => 'correctivo', 'reparacion' => 'hardware', 'costo' => 358000, 'estado' => 'terminado', 'anulado' => false,
        ]);
        $orden->stocks()->attach($this->stock()->id, ['cantidad' => 2, 'precio_unitario' => 104000]);
        $orden->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        foreach (['mantenimientos.factura', 'mantenimientos.factura_pos'] as $vista) {
            $html = view($vista, ['mantenimiento' => $orden])->render();
            $this->assertStringContainsString('Servicio técnico / mano de obra', $html, $vista);
            $this->assertStringContainsString('$ 150.000', $html, $vista.': 358.000 - 2 × 104.000');
            $this->assertStringContainsString('Total del servicio:', $html, $vista);
        }
    }

    public function test_una_instalacion_nueva_no_queda_con_la_empresa_de_ejemplo(): void
    {
        DB::table('configuraciones')->delete();
        Configuracion::create(['nombre' => 'Tecni Systemas', 'nit' => '900.000.000-1', 'telefono' => '300 000 0000']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Configuracion::count());
        $this->assertNotSame('900.000.000-1', Configuracion::first()->nit);

        // Si el dueño ya cambió sus datos, el seeder no los toca.
        Configuracion::first()->update(['nit' => '123-4']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame('123-4', Configuracion::first()->nit);
    }

    // ── Respaldos ───────────────────────────────────────────────────────────

    public function test_la_lista_de_respaldos_muestra_la_hora_local(): void
    {
        $dir = storage_path('app/backups');
        $archivo = $dir.DIRECTORY_SEPARATOR.'db_hora_local.sql';
        file_put_contents($archivo, '-- prueba');
        $mtime = mktime(14, 25, 0, 11, 16, 2026);
        touch($archivo, $mtime);

        $fila = collect($this->actingAs($this->admin)->getJson(route('backups.config'))->json('files'))
            ->firstWhere('name', 'db_hora_local.sql');
        @unlink($archivo);

        $this->assertNotNull($fila);
        $esperada = Carbon::createFromTimestamp($mtime, config('app.timezone'))->format('d/m/Y h:i A');
        $this->assertSame($esperada, $fila['fecha']);
        $this->assertStringContainsString('02:25 PM', $fila['fecha']);
    }
}
