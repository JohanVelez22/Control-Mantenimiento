<?php

namespace Tests\Unit;

use App\Models\BajaStock;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'identificacion' => '900123456',
            'tipo_entidad' => 'empresa',
            'email' => 'prov@test.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 100',
        ]);
    }

    public function test_auto_calculates_precio_venta_when_not_provided(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-AUTO-1',
            'producto' => 'Auto Precio Venta',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 30,
        ]);

        $this->assertEquals(130000, $stock->precio_venta);
    }

    public function test_auto_calculates_precio_tecnico_when_not_provided(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-AUTO-2',
            'producto' => 'Auto Precio Tecnico',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 40,
        ]);

        $this->assertEquals(120000, $stock->precio_tecnico);
    }

    public function test_respects_explicit_precio_venta(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-EXPLICIT-1',
            'producto' => 'Precio Explicito',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 30,
            'precio_venta' => 200000,
        ]);

        $this->assertEquals(200000, $stock->precio_venta);
    }

    public function test_respects_explicit_precio_tecnico(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-EXPLICIT-2',
            'producto' => 'Precio Tecnico Explicito',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 40,
            'precio_tecnico' => 180000,
        ]);

        $this->assertEquals(180000, $stock->precio_tecnico);
    }

    public function test_total_unidades_baja_attribute(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-BAJA-ATTR',
            'producto' => 'Test Bajas',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 80000,
            'utilidad' => 50,
        ]);

        BajaStock::create([
            'stock_id' => $stock->id,
            'user_id' => $this->admin->id,
            'cantidad' => 3,
            'precio_compra_unitario' => 80000,
            'costo_total_perdida' => 240000,
            'motivo' => 'dano_taller',
        ]);

        BajaStock::create([
            'stock_id' => $stock->id,
            'user_id' => $this->admin->id,
            'cantidad' => 2,
            'precio_compra_unitario' => 80000,
            'costo_total_perdida' => 160000,
            'motivo' => 'perdida_merma',
        ]);

        $this->assertEquals(5, $stock->total_unidades_baja);
    }

    public function test_total_perdida_bajas_attribute(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-PERDIDA-ATTR',
            'producto' => 'Test Perdida',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 80000,
            'utilidad' => 50,
        ]);

        BajaStock::create([
            'stock_id' => $stock->id,
            'user_id' => $this->admin->id,
            'cantidad' => 3,
            'precio_compra_unitario' => 80000,
            'costo_total_perdida' => 240000,
            'motivo' => 'dano_taller',
        ]);

        BajaStock::create([
            'stock_id' => $stock->id,
            'user_id' => $this->admin->id,
            'cantidad' => 2,
            'precio_compra_unitario' => 80000,
            'costo_total_perdida' => 160000,
            'motivo' => 'perdida_merma',
        ]);

        $this->assertEquals(400000, $stock->total_perdida_bajas);
    }

    public function test_tiene_disponible_returns_true_when_sufficient(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-DISP-1',
            'producto' => 'Test Disponible',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $this->assertTrue($stock->tieneDisponible(5));
        $this->assertTrue($stock->tieneDisponible(10));
    }

    public function test_tiene_disponible_returns_false_when_insufficient(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-DISP-2',
            'producto' => 'Test No Disponible',
            'categoria' => 'Test',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $this->assertFalse($stock->tieneDisponible(10));
    }

    public function test_incrementar_stock_delegates_to_service(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-INC-1',
            'producto' => 'Test Incrementar',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $stock->incrementarStock(5);
        $stock->refresh();
        $this->assertEquals(15, $stock->cantidad);
    }

    public function test_decrementar_stock_delegates_to_service(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-DEC-1',
            'producto' => 'Test Decrementar',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $stock->decrementarStock(3);
        $stock->refresh();
        $this->assertEquals(7, $stock->cantidad);
    }

    public function test_decrementar_stock_throws_on_insufficient(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-DEC-2',
            'producto' => 'Test Decrementar Error',
            'categoria' => 'Test',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $this->expectException(\DomainException::class);
        $stock->decrementarStock(10);
    }

    public function test_scope_activos_filters_correctly(): void
    {
        Stock::create([
            'codigo' => 'STK-ACT-1',
            'producto' => 'Activo',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'active' => true,
        ]);

        Stock::create([
            'codigo' => 'STK-ACT-2',
            'producto' => 'Inactivo',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'active' => false,
        ]);

        $activos = Stock::activos()->get();
        $this->assertEquals(1, $activos->count());
        $this->assertEquals('STK-ACT-1', $activos->first()->codigo);
    }
}
