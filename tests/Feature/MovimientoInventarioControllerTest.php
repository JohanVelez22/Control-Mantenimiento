<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimientoInventarioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected Stock $stock;

    protected Proveedor $proveedor;

    protected Cliente $cliente;

    protected ConceptoCaja $concepto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->stock = Stock::create([
            'codigo' => 'STK-TEST-1',
            'producto' => 'Producto Test',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'precio_compra' => 30000,
            'utilidad' => 50,
            'precio_venta' => 50000,
            'precio_tecnico' => 40000,
            'active' => true,
        ]);

        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'identificacion' => '900123456',
            'tipo_entidad' => 'empresa',
            'email' => 'prov@test.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 100',
        ]);

        $this->cliente = Cliente::factory()->create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'identificacion' => '123456789',
            'movil' => '3001234567',
        ]);

        $this->concepto = ConceptoCaja::create(['nombre' => 'Compra de Inventario']);
    }

    // ─── COMPRA ───

    public function test_admin_can_view_create_compra(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventario.compra.create'));
        $response->assertStatus(200);
    }

    public function test_tecnico_can_view_create_compra(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('inventario.compra.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_registrar_compra(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 50000,
            'observaciones' => 'Compra de prueba',
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 5,
                    'precio_unitario' => 30000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facturas', [
            'tipo_movimiento' => 'compra',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
        ]);
        $this->stock->refresh();
        $this->assertEquals(15, $this->stock->cantidad);
    }

    public function test_compra_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => '',
            'fecha' => '',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['facturable_global', 'fecha', 'items']);
    }

    public function test_compra_rejects_pago_mayor_total(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 200000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 1,
                    'precio_unitario' => 30000,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_compra_creates_caja_movement_when_pagado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 50000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 25000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('movimiento_cajas', [
            'tipo_movimiento' => 'egreso',
            'descripcion' => 'Pago compra #CP-1',
        ]);
    }

    public function test_compra_shows_saldo_pendiente_alert(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$this->proveedor->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 10000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 30000,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHas('alert_compra_pendiente');
    }

    // ─── VENTA ───

    public function test_admin_can_view_create_venta(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventario.venta.create'));
        $response->assertStatus(200);
    }

    public function test_tecnico_can_view_create_venta(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('inventario.venta.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_registrar_venta(): void
    {
        $this->stock->cantidad = 10;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 50000,
            'observaciones' => 'Venta de prueba',
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facturas', [
            'tipo_movimiento' => 'venta',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
        ]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);
    }

    public function test_venta_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => '',
            'fecha' => '',
            'total_pagado' => '',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['facturable_global', 'fecha', 'items']);
    }

    public function test_venta_rejects_pago_mayor_total(): void
    {
        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 200000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 1,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_venta_rejects_insufficient_stock(): void
    {
        $this->stock->cantidad = 1;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 50000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 5,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', $response->getSession()->get('error'));
    }

    public function test_venta_creates_caja_movement_when_pagado(): void
    {
        $this->stock->cantidad = 10;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 50000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 1,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('movimiento_cajas', [
            'tipo_movimiento' => 'ingreso',
            'descripcion' => 'Cobro venta #VT-1',
        ]);
    }

    public function test_venta_shows_saldo_pendiente_alert(): void
    {
        $this->stock->cantidad = 10;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 10000,
            'items' => [
                [
                    'stock_id' => $this->stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $response->assertSessionHas('alert_venta_pendiente');
    }

    // ─── FACTURAS LIST ───

    public function test_admin_can_view_facturas_index(): void
    {
        Factura::create([
            'numero_factura' => 'CP-1',
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'CP-2',
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 200000,
            'total_pagado' => 200000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'CP-3',
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 300000,
            'total_pagado' => 300000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'VT-2',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 200000,
            'total_pagado' => 200000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas'));
        $response->assertStatus(200);
    }

    public function test_facturas_index_filters_by_tipo(): void
    {
        Factura::create([
            'numero_factura' => 'CP-1',
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas', ['tipo' => 'compra']));
        $response->assertStatus(200);
    }

    public function test_facturas_index_filters_by_estado(): void
    {
        Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'VT-2',
            'tipo_movimiento' => 'venta',
            'estado' => 'anulada',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 200000,
            'total_pagado' => 0,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas', ['estado' => 'emitida']));
        $response->assertStatus(200);
    }

    public function test_facturas_index_filters_by_date_range(): void
    {
        Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today()->subDays(10),
            'user_id' => $this->admin->id,
        ]);
        Factura::create([
            'numero_factura' => 'VT-2',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas', [
            'fecha_desde' => Carbon::today()->toDateString(),
            'fecha_hasta' => Carbon::today()->toDateString(),
        ]));
        $response->assertStatus(200);
    }

    // ─── FACTURA SHOW ───

    public function test_admin_can_view_factura_show(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas.show', $factura));
        $response->assertStatus(200);
    }

    public function test_factura_show_loads_items_and_abonos(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 50000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);

        MovimientoCaja::create([
            'factura_id' => $factura->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test',
            'concepto_id' => $this->concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas.show', $factura));
        $response->assertStatus(200);
    }

    // ─── FACTURA PRINT ───

    public function test_admin_can_print_factura(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas.print', $factura));
        $response->assertStatus(200);
    }

    // ─── FACTURA ANULAR/REACTIVAR ───

    public function test_admin_can_anular_factura_compra(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'CP-1',
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 150000,
            'total_pagado' => 150000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 5,
            'precio_unitario' => 30000,
        ]);
        // Increment stock manually since Factura::create doesn't trigger stock update
        $this->stock->incrementarStock(5);
        $this->stock->refresh(); // 15

        $response = $this->actingAs($this->admin)->post(route('inventario.facturas.anular', $factura), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad); // Se devolvió el stock
    }

    public function test_admin_can_anular_factura_venta(): void
    {
        $this->stock->cantidad = 8;
        $this->stock->save();

        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventario.facturas.anular', $factura), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad); // Se devolvió el stock
    }

    public function test_admin_can_reactivar_factura_compra(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'CP-1',
            'tipo_movimiento' => 'compra',
            'estado' => 'anulada',
            'facturable_id' => $this->proveedor->id,
            'facturable_type' => Proveedor::class,
            'total_documento' => 150000,
            'total_pagado' => 0,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 5,
            'precio_unitario' => 30000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventario.facturas.anular', $factura), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $factura->refresh();
        $this->assertEquals('pendiente_pago', $factura->estado);
        $this->stock->refresh();
        $this->assertEquals(15, $this->stock->cantidad); // Se volvió a incrementar
    }

    public function test_anular_reactivar_requires_password_confirm(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        // Admin doesn't need password, test with tecnico
        $response = $this->actingAs($this->tecnico)->post(route('inventario.facturas.anular', $factura), []);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('contraseña', $response->getSession()->get('error'));
    }

    public function test_cannot_anular_factura_in_closed_caja(): void
    {
        $fechaCerrada = Carbon::today()->subDays(2)->toDateString();
        CierreCaja::create([
            'fecha' => $fechaCerrada,
            'total_ingresos' => 100000,
            'total_egresos' => 0,
            'efectivo' => 100000,
            'consignacion' => 0,
            'saldo_final' => 100000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => $fechaCerrada,
            'user_id' => $this->admin->id,
        ]);

        // Create MovimientoCaja for the factura (controller checks descripcion containing #VT-1)
        MovimientoCaja::create([
            'factura_id' => $factura->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'persona' => 'Test',
            'descripcion' => "Cobro venta #{$factura->numero_factura}",
            'concepto_id' => $this->concepto->id,
            'fecha' => $fechaCerrada,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('inventario.facturas.anular', $factura), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ─── FACTURA EDIT ───

    public function test_admin_can_view_edit_factura(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('inventario.facturas.edit', $factura));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_factura(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'pendiente_pago',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 50000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);
        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 100000,
            'observaciones' => 'Actualizada',
            'existing_items' => [
                [
                    'id' => $item->id,
                    'stock_id' => $this->stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 50000,
                ],
            ],
            'new_items' => [],
        ]);

        $response->assertRedirect();
        $factura->refresh();
        $this->assertEquals(100000, $factura->total_pagado);
        $this->assertEquals('emitida', $factura->estado);
    }

    public function test_update_factura_validates_required_fields(): void
    {
        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), [
            'facturable_global' => '',
            'fecha' => '',
        ]);

        $response->assertSessionHasErrors(['facturable_global', 'fecha']);
    }

    public function test_update_factura_rejects_closed_date(): void
    {
        $fechaCerrada = Carbon::today()->subDays(2)->toDateString();
        CierreCaja::create([
            'fecha' => $fechaCerrada,
            'total_ingresos' => 100000,
            'total_egresos' => 0,
            'efectivo' => 100000,
            'consignacion' => 0,
            'saldo_final' => 100000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $factura = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 100000,
            'fecha' => Carbon::today(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), [
            'facturable_global' => 'Cliente:'.$this->cliente->id,
            'fecha' => $fechaCerrada,
            'total_pagado' => 50000,
            'existing_items' => [],
            'new_items' => [],
        ]);

        $response->assertSessionHasErrors('fecha');
    }
}
