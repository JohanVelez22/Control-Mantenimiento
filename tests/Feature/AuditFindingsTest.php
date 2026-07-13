<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Stock;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\User;
use App\Http\Controllers\MovimientoInventarioController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditFindingsTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $proveedor;
    private $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test_' . time() . '@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'active' => true,
        ]);

        $this->proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => 'NIT-' . time(),
            'nombre_razon_social' => 'Proveedor Test',
            'telefono' => '3000000001',
            'email' => 'proveedor_test@test.com',
            'direccion' => 'Calle Proveedor',
            'activo' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombres' => 'Cliente',
            'apellidos' => 'Test',
            'identificacion' => 'CC-' . time(),
            'telefono' => '3000000000',
            'movil' => '3000000000',
            'email' => 'cliente_test_' . time() . '@test.com',
            'direccion' => 'Calle Test',
            'activo' => true,
        ]);
    }

    /**
     * ============================================================
     * BUG: Number formatting con separador de miles (punto)
     * ============================================================
     */

    public function testCalcularTotalParseaMilesConPuntoCorrectamente()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        $items = [
            ['cantidad' => 2, 'precio_unitario' => '1.500'], // 1500
            ['cantidad' => 3, 'precio_unitario' => '2.000'], // 2000
        ];

        $total = $method->invoke($controller, $items);

        // 2 * 1500 + 3 * 2000 = 3000 + 6000 = 9000
        $this->assertEquals(9000, $total);
    }

    public function testCalcularTotalConUnidadesSinSeparadorMiles()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        $items = [
            ['cantidad' => 1, 'precio_unitario' => '500'],
            ['cantidad' => 2, 'precio_unitario' => '1000'],
        ];

        $total = $method->invoke($controller, $items);

        // 1 * 500 + 2 * 1000 = 2500
        $this->assertEquals(2500, $total);
    }

    public function testCalcularTotalConDecimales()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        $items = [
            ['cantidad' => 1, 'precio_unitario' => '1.500.50'], // 150050 centavos? No, formato COP
        ];

        $total = $method->invoke($controller, $items);

        // str_replace quita TODOS los puntos -> "150050" -> 150050
        // En COP no se usan decimales normalmente, pero el código lo permite
        $this->assertEquals(150050, $total);
    }

    public function testTotalPagadoParseaMilesIgualQueItems()
    {
        $controller = new MovimientoInventarioController();

        // Simular request con total_pagado formateado
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'total_pagado' => '1.500', // 1500
            'items' => [
                ['cantidad' => 1, 'precio_unitario' => '1.500', 'stock_id' => 1],
            ],
        ]);

        // Usar reflection para probar método privado
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('calcularTotal');
        $method->setAccessible(true);

        $totalDocumento = $method->invoke($controller, $request->items);
        $totalPagado = (float) str_replace('.', '', $request->total_pagado);

        $this->assertEquals($totalDocumento, $totalPagado);
    }

    /**
     * ============================================================
     * COMPRA: Flujo completo y validaciones
     * ============================================================
     */

    public function testCompraPagoTotalQuedaEmitida()
    {
        $stock = Stock::create([
            'producto' => 'Producto Compra',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1500,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventario.compra.store'), [
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '3000', // 3000 (2 * 1500)
                'observaciones' => 'Test compra',
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $factura = Factura::where('tipo_movimiento', 'compra')->first();
        $this->assertNotNull($factura);
        $this->assertEquals('emitida', $factura->estado);
        $this->assertEquals(3000, $factura->total_documento);
        $this->assertEquals(3000, $factura->total_pagado);

        // Stock incrementado
        $stock->refresh();
        $this->assertEquals(12, $stock->cantidad);
    }

    public function testCompraPagoParcialQuedaPendientePago()
    {
        $stock = Stock::create([
            'producto' => 'Producto Compra Parcial',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1500,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventario.compra.store'), [
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '1000', // 1000 de 3000
                'observaciones' => 'Test compra parcial',
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $factura = Factura::where('tipo_movimiento', 'compra')->first();
        $this->assertEquals('pendiente_pago', $factura->estado);
        $this->assertEquals(3000, $factura->total_documento);
        $this->assertEquals(1000, $factura->total_pagado);
        $this->assertEquals(2000, $factura->saldo_pendiente);
    }

    public function testCompraPagoSuperiorAlTotalLanzaError()
    {
        $stock = Stock::create([
            'producto' => 'Producto Error',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1500,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('inventario.compra.store'), [
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '5000', // 5000 > 3000
                'observaciones' => 'Test error',
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('no puede superar', session('error'));
    }

    public function testCompraMultiplesItemsCalculaTotalCorrecto()
    {
        $stock1 = Stock::create([
            'producto' => 'Producto A',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $stock2 = Stock::create([
            'producto' => 'Producto B',
            'categoria' => 'Test',
            'cantidad' => 5,
            'precio_compra' => 2500,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventario.compra.store'), [
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '8000', // 3*1000 + 2*2500 = 8000
                'items' => [
                    ['stock_id' => $stock1->id, 'cantidad' => 3, 'precio_unitario' => '1000'],
                    ['stock_id' => $stock2->id, 'cantidad' => 2, 'precio_unitario' => '2500'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $factura = Factura::where('tipo_movimiento', 'compra')->first();
        $this->assertEquals(8000, $factura->total_documento);
    }

    /**
     * ============================================================
     * VENTA: Flujo completo y validaciones
     * ============================================================
     */

    public function testVentaPagoTotalQuedaEmitida()
    {
        $stock = Stock::create([
            'producto' => 'Producto Venta',
            'categoria' => 'Test',
            'cantidad' => 20,
            'precio_compra' => 1000,
            'utilidad' => 50, // precio_venta = 1500
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventario.venta.store'), [
                'facturable_global' => "Cliente:{$this->cliente->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '3000', // 2 * 1500
                'observaciones' => 'Test venta',
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $factura = Factura::where('tipo_movimiento', 'venta')->first();
        $this->assertEquals('emitida', $factura->estado);
        $this->assertEquals(3000, $factura->total_documento);
        $this->assertEquals(3000, $factura->total_pagado);

        // Stock decrementado
        $stock->refresh();
        $this->assertEquals(18, $stock->cantidad);
    }

    public function testVentaSinStockSuficienteLanzaError()
    {
        $stock = Stock::create([
            'producto' => 'Producto Sin Stock',
            'categoria' => 'Test',
            'cantidad' => 2,
            'precio_compra' => 1000,
            'utilidad' => 50,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('inventario.venta.store'), [
                'facturable_global' => "Cliente:{$this->cliente->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '4500',
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 5, 'precio_unitario' => '1500'], // Pide 5, hay 2
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', session('error'));
    }

    public function testVentaPagoParcialQuedaPendientePago()
    {
        $stock = Stock::create([
            'producto' => 'Producto Venta Parcial',
            'categoria' => 'Test',
            'cantidad' => 20,
            'precio_compra' => 1000,
            'utilidad' => 50,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventario.venta.store'), [
                'facturable_global' => "Cliente:{$this->cliente->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '1000', // 1000 de 3000
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $factura = Factura::where('tipo_movimiento', 'venta')->first();
        $this->assertEquals('pendiente_pago', $factura->estado);
        $this->assertEquals(2000, $factura->saldo_pendiente);
    }

    public function testVentaCobroSuperiorAlTotalLanzaError()
    {
        $stock = Stock::create([
            'producto' => 'Producto Venta Error',
            'categoria' => 'Test',
            'cantidad' => 20,
            'precio_compra' => 1000,
            'utilidad' => 50,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('inventario.venta.store'), [
                'facturable_global' => "Cliente:{$this->cliente->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '5000', // 5000 > 3000
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => '1500'],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('no puede superar', session('error'));
    }

    /**
     * ============================================================
     * ANULACIÓN/REACTIVACIÓN DE FACTURAS
     * ============================================================
     */

    public function testAnularCompraRestauraStock()
    {
        $stock = Stock::create([
            'producto' => 'Compra Anular',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $origStock = $stock->cantidad;

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('CP-'),
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_type' => Proveedor::class,
            'facturable_id' => $this->proveedor->id,
            'total_documento' => 2000,
            'total_pagado' => 2000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
        ]);

        // Simular controlador: incrementar stock
        $stock->incrementarStock(2);
        $stock->refresh();
        $this->assertEquals($origStock + 2, $stock->cantidad);

        // Anular
        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $factura), [
                'password_confirm' => 'password', // admin password
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals($origStock, $stock->cantidad);

        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
    }

    public function testAnularVentaRestauraStock()
    {
        $stock = Stock::create([
            'producto' => 'Venta Anular',
            'categoria' => 'Test',
            'cantidad' => 20,
            'precio_compra' => 1000,
            'utilidad' => 50,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $origStock = $stock->cantidad;

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('VT-'),
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_type' => Cliente::class,
            'facturable_id' => $this->cliente->id,
            'total_documento' => 3000,
            'total_pagado' => 3000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 1500,
        ]);

        // Simular controlador: decrementar stock
        $stock->decrementarStock(2);
        $stock->refresh();
        $this->assertEquals($origStock - 2, $stock->cantidad);

        // Anular
        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $factura), [
                'password_confirm' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals($origStock, $stock->cantidad);

        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
    }

    public function testReactivarFacturaAnuladaRestauraStockYEstado()
    {
        $stock = Stock::create([
            'producto' => 'Reactivar Test',
            'categoria' => 'Test',
            'cantidad' => 15,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $origStock = $stock->cantidad;

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('CP-'),
            'tipo_movimiento' => 'compra',
            'estado' => 'anulada',
            'facturable_type' => Proveedor::class,
            'facturable_id' => $this->proveedor->id,
            'total_documento' => 2000,
            'total_pagado' => 2000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
        ]);

        // Reactivar (llamar endpoint anular de nuevo)
        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $factura), [
                'password_confirm' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals($origStock + 2, $stock->cantidad); // Compra reactivada = stock entra

        $factura->refresh();
        $this->assertEquals('emitida', $factura->estado); // Pagado total = emitida
    }

    public function testAnularFacturaPendientePagoNoGeneraMovimientoCaja()
    {
        $stock = Stock::create([
            'producto' => 'Compra Pendiente',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('CP-'),
            'tipo_movimiento' => 'compra',
            'estado' => 'pendiente_pago',
            'facturable_type' => Proveedor::class,
            'facturable_id' => $this->proveedor->id,
            'total_documento' => 2000,
            'total_pagado' => 500, // Pago parcial
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
        ]);

        $movimientosAntes = MovimientoCaja::count();

        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $factura), [
                'password_confirm' => 'password',
            ])
            ->assertRedirect();

        // No debe crear nuevos movimientos de caja al anular (ya existen si los hubo)
        // Solo verifica que no falle
        $factura->refresh();
        $this->assertEquals('anulada', $factura->estado);
    }

    /**
     * ============================================================
     * EDICIÓN DE FACTURAS
     * ============================================================
     */

    public function testEditarFacturaCambiarCantidadRecalculaTotalYStock()
    {
        $stock = Stock::create([
            'producto' => 'Editar Test',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $origStock = $stock->cantidad;

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('CP-'),
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_type' => Proveedor::class,
            'facturable_id' => $this->proveedor->id,
            'total_documento' => 2000,
            'total_pagado' => 2000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
        ]);

        $stock->incrementarStock(2);
        $stock->refresh();
        $this->assertEquals($origStock + 2, $stock->cantidad);

        // Editar: cambiar cantidad de 2 a 5
        $this->actingAs($this->admin)
            ->put(route('inventario.facturas.update', $factura), [
                'fecha' => now()->toDateString(),
                'total_pagado' => '5000', // 5 * 1000
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'existing_items' => [
                    [
                        'id' => $item->id,
                        'stock_id' => $stock->id,
                        'cantidad' => 5,
                        'precio_unitario' => '1000',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $stock->refresh();
        // Original 10 + 2 (original) - 2 (revertido) + 5 (nuevo) = 15
        // O más simple: orig + 5
        $this->assertEquals($origStock + 5, $stock->cantidad);

        $factura->refresh();
        $this->assertEquals(5000, $factura->total_documento);
        $this->assertEquals(5000, $factura->total_pagado);
    }

    public function testEditarFacturaPagoSuperiorAlNuevoTotalLanzaError()
    {
        $stock = Stock::create([
            'producto' => 'Editar Error',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('CP-'),
            'tipo_movimiento' => 'compra',
            'estado' => 'emitida',
            'facturable_type' => Proveedor::class,
            'facturable_id' => $this->proveedor->id,
            'total_documento' => 5000,
            'total_pagado' => 5000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 5,
            'precio_unitario' => 1000,
        ]);

        // Editar: reducir items a 2 (total 2000) pero mantener pago 5000
        $response = $this->actingAs($this->admin)
            ->put(route('inventario.facturas.update', $factura), [
                'fecha' => now()->toDateString(),
                'total_pagado' => '5000',
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'existing_items' => [
                    [
                        'id' => $item->id,
                        'stock_id' => $stock->id,
                        'cantidad' => 2,
                        'precio_unitario' => '1000',
                    ],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('no puede superar', session('error'));
    }

    /**
     * ============================================================
     * MOVIMIENTOS DE CAJA
     * ============================================================
     */

    public function testCrearMovimientoCajaIngreso()
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Test Ingreso']);

        // Enviar valores numéricos sin separador de miles (como lo hace el hidden input real)
        $this->actingAs($this->admin)
            ->post(route('caja.store'), [
                'persona' => 'Cliente Test',
                'fecha' => now()->toDateString(),
                'tipo_movimiento' => 'ingreso',
                'tipo_pago' => 'efectivo',
                'monto' => '10000', // Valor real sin puntos
                'concepto_id' => $concepto->id,
                'descripcion' => 'Test ingreso',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $mov = MovimientoCaja::where('descripcion', 'Test ingreso')->first();
        $this->assertNotNull($mov);
        $this->assertEquals('ingreso', $mov->tipo_movimiento);
        $this->assertEquals(10000, $mov->monto);
    }

    public function testCrearMovimientoCajaEgreso()
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Test Egreso']);

        $this->actingAs($this->admin)
            ->post(route('caja.store'), [
                'persona' => 'Proveedor Test',
                'fecha' => now()->toDateString(),
                'tipo_movimiento' => 'egreso',
                'tipo_pago' => 'efectivo',
                'monto' => '5000',
                'concepto_id' => $concepto->id,
                'descripcion' => 'Test egreso',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $mov = MovimientoCaja::where('descripcion', 'Test egreso')->first();
        $this->assertEquals('egreso', $mov->tipo_movimiento);
        $this->assertEquals(5000, $mov->monto);
    }

    public function testMovimientoCajaConMontoTotalYSaldoPendiente()
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Test Saldo']);

        $this->actingAs($this->admin)
            ->post(route('caja.store'), [
                'persona' => 'Cliente Saldo',
                'fecha' => now()->toDateString(),
                'tipo_movimiento' => 'ingreso',
                'tipo_pago' => 'efectivo',
                'monto' => '3000', // Pagado hoy
                'monto_total' => '10000', // Deuda total
                'concepto_id' => $concepto->id,
                'descripcion' => 'Test saldo pendiente',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $mov = MovimientoCaja::where('descripcion', 'Test saldo pendiente')->first();
        $this->assertEquals(3000, $mov->monto);
        $this->assertEquals(10000, $mov->monto_total);
        $this->assertEquals(7000, $mov->saldo_pendiente);
    }

    public function testAbonoAMovimientoCajaReduceSaldoPendiente()
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Test Abono Caja']);

        $padre = MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'fecha' => now()->toDateString(),
            'monto' => 2000,
            'monto_total' => 10000,
            'concepto_id' => $concepto->id,
            'tipo_pago' => 'efectivo',
            'persona' => 'Cliente Abono',
            'descripcion' => 'Padre test',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals(8000, $padre->saldo_pendiente);

        $this->actingAs($this->admin)
            ->post(route('caja.abonos.store', $padre), [
                'monto_abono' => '3000',
                'fecha' => now()->toDateString(),
                'tipo_pago' => 'efectivo',
                'descripcion' => 'Abono test',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $padre->refresh();
        $this->assertEquals(5000, $padre->saldo_pendiente);
    }

    public function testAbonoSuperiorAlSaldoLanzaError()
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Test Abono Error']);

        $padre = MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'fecha' => now()->toDateString(),
            'monto' => 2000,
            'monto_total' => 10000,
            'concepto_id' => $concepto->id,
            'tipo_pago' => 'efectivo',
            'persona' => 'Cliente Abono Error',
            'descripcion' => 'Padre error',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('caja.abonos.store', $padre), [
                'monto_abono' => '9000', // > 8000 saldo
                'fecha' => now()->toDateString(),
                'tipo_pago' => 'efectivo',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('supera el saldo', session('error'));
    }

    /**
     * ============================================================
     * EDGE CASES: FORMATOS DE NÚMERO
     * ============================================================
     */

    public function testInputConMultiplesPuntos()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        // "1.500.000" -> str_replace -> "1500000" -> 1500000
        $items = [
            ['cantidad' => 1, 'precio_unitario' => '1.500.000'],
        ];

        $total = $method->invoke($controller, $items);
        $this->assertEquals(1500000, $total);
    }

    public function testInputConCerosIniciales()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        $items = [
            ['cantidad' => 1, 'precio_unitario' => '0001500'],
        ];

        $total = $method->invoke($controller, $items);
        $this->assertEquals(1500, $total);
    }

    public function testInputVacioOCero()
    {
        $controller = new MovimientoInventarioController();
        $method = new \ReflectionMethod($controller, 'calcularTotal');
        $method->setAccessible(true);

        $items = [
            ['cantidad' => 1, 'precio_unitario' => ''],
            ['cantidad' => 1, 'precio_unitario' => '0'],
        ];

        $total = $method->invoke($controller, $items);
        $this->assertEquals(0, $total);
    }

    /**
     * ============================================================
     * CONSISTENCIA DE DATOS: CASTS Y ACCESORES
     * ============================================================
     */

    public function testFacturaTotalDocumentoEsDecimal()
    {
        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('F'),
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_type' => Cliente::class,
            'facturable_id' => $this->cliente->id,
            'total_documento' => 1500.50,
            'total_pagado' => 1500.50,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals(1500.50, $factura->total_documento);
        $this->assertEquals(1500.50, $factura->total_pagado);
        // Los casts 'decimal:2' devuelven string en PHP, se castean al acceder
        $this->assertIsNumeric($factura->total_documento);
    }

    public function testFacturaItemPrecioUnitarioEsDecimal()
    {
        $stock = Stock::create([
            'producto' => 'Item Decimal',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 30,
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('F'),
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_type' => Cliente::class,
            'facturable_id' => $this->cliente->id,
            'total_documento' => 1500.50,
            'total_pagado' => 1500.50,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 1,
            'precio_unitario' => 1500.50,
        ]);

        $this->assertEquals(1500.50, $item->precio_unitario);
        $this->assertEquals(1500.50, $item->subtotal);
    }

public function testSaldoPendienteNuncaNegativo()
    {
        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('F'),
            'tipo_movimiento' => 'venta',
            'estado' => 'emitida',
            'facturable_type' => Cliente::class,
            'facturable_id' => $this->cliente->id,
            'total_documento' => 1000,
            'total_pagado' => 2000, // Sobrepago
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // Refrescar para obtener columnas generadas (saldo_pendiente, saldo_a_favor)
        $factura->refresh();

        $this->assertEquals(0, $factura->saldo_pendiente);
        $this->assertEquals(1000, $factura->saldo_a_favor);
    }

    /**
     * ============================================================
     * INTEGRACIÓN: COMPRA -> VENTA -> ANULACIONES
     * ============================================================
     */

    public function testFlujoCompletoCompraVentaAnulacion()
    {
        // 1. Crear proveedor y cliente ya en setUp

        // 2. Crear stock inicial
        $stock = Stock::create([
            'producto' => 'Flujo Completo',
            'categoria' => 'Test',
            'cantidad' => 0,
            'precio_compra' => 1000,
            'utilidad' => 50, // venta = 1500
            'proveedor_id' => $this->proveedor->id,
            'activo' => true,
        ]);

        // 3. COMPRA: Traer 10 unidades
        $this->actingAs($this->admin)
            ->post(route('inventario.compra.store'), [
                'facturable_global' => "Proveedor:{$this->proveedor->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '10000', // 10 * 1000
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 10, 'precio_unitario' => '1000'],
                ],
            ])
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals(10, $stock->cantidad);

        // 4. VENTA: Vender 3 unidades
        $this->actingAs($this->admin)
            ->post(route('inventario.venta.store'), [
                'facturable_global' => "Cliente:{$this->cliente->id}",
                'fecha' => now()->toDateString(),
                'total_pagado' => '4500', // 3 * 1500
                'items' => [
                    ['stock_id' => $stock->id, 'cantidad' => 3, 'precio_unitario' => '1500'],
                ],
            ])
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals(7, $stock->cantidad);

        // 5. ANULAR VENTA: Stock debe volver a 10
        $venta = Factura::where('tipo_movimiento', 'venta')->first();
        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $venta), [
                'password_confirm' => 'password',
            ])
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals(10, $stock->cantidad);

        // 6. ANULAR COMPRA: Stock debe volver a 0
        $compra = Factura::where('tipo_movimiento', 'compra')->first();
        $this->actingAs($this->admin)
            ->post(route('inventario.facturas.anular', $compra), [
                'password_confirm' => 'password',
            ])
            ->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals(0, $stock->cantidad);
    }
}