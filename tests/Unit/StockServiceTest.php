<?php

namespace Tests\Unit;

use App\Models\BajaStock;
use App\Models\Stock;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->stock = Stock::factory()->create([
            'cantidad' => 10,
            'precio_compra' => 30000,
            'precio_venta' => 50000,
            'precio_tecnico' => 40000,
            'active' => true,
        ]);
    }

    // ─── ENTRADA ───

    public function test_entrada_increments_stock(): void
    {
        $service = new StockService;
        $result = $service->entrada($this->stock, 5);

        $this->assertInstanceOf(Stock::class, $result);
        $this->assertEquals(15, $result->cantidad);
        $this->stock->refresh();
        $this->assertEquals(15, $this->stock->cantidad);
    }

    public function test_entrada_accepts_stock_id(): void
    {
        $service = new StockService;
        $result = $service->entrada($this->stock->id, 3);

        $this->assertEquals(13, $result->cantidad);
    }

    public function test_entrada_throws_on_zero_or_negative(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('mayor que cero');
        $service->entrada($this->stock, 0);
    }

    public function test_entrada_throws_on_negative(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $service->entrada($this->stock, -1);
    }

    public function test_entrada_uses_transaction_and_locks_row(): void
    {
        $service = new StockService;
        $service->entrada($this->stock, 10);
        $this->stock->refresh();
        $this->assertEquals(20, $this->stock->cantidad);
    }

    // ─── SALIDA ───

    public function test_salida_decrements_stock(): void
    {
        $service = new StockService;
        $result = $service->salida($this->stock, 4);

        $this->assertEquals(6, $result->cantidad);
        $this->stock->refresh();
        $this->assertEquals(6, $this->stock->cantidad);
    }

    public function test_salida_accepts_stock_id(): void
    {
        $service = new StockService;
        $result = $service->salida($this->stock->id, 2);

        $this->assertEquals(8, $result->cantidad);
    }

    public function test_salida_throws_on_insufficient_stock(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Stock insuficiente');
        $service->salida($this->stock, 15);
    }

    public function test_salida_throws_on_zero_or_negative(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('mayor que cero');
        $service->salida($this->stock, 0);
    }

    public function test_salida_throws_on_negative(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $service->salida($this->stock, -1);
    }

    // ─── DAR DE BAJA ───

    public function test_dar_de_baja_decrements_stock_and_creates_baja_record(): void
    {
        $service = new StockService;
        $baja = $service->darDeBaja($this->stock, 3, 'dano_taller', 'Golpe en taller', $this->admin->id);

        $this->assertInstanceOf(BajaStock::class, $baja);
        $this->assertEquals(3, $baja->cantidad);
        $this->assertEquals('dano_taller', $baja->motivo);
        $this->assertEquals('Golpe en taller', $baja->observacion);
        $this->assertEquals($this->admin->id, $baja->user_id);
        $this->assertEquals(90000, $baja->costo_total_perdida); // 3 * 30000
        $this->stock->refresh();
        $this->assertEquals(7, $this->stock->cantidad);
    }

    public function test_dar_de_baja_accepts_stock_id(): void
    {
        $service = new StockService;
        $baja = $service->darDeBaja($this->stock->id, 2, 'defectuoso_fabrica');

        $this->assertEquals(2, $baja->cantidad);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);
    }

    public function test_dar_de_baja_throws_on_zero_or_negative(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('mayor que cero');
        $service->darDeBaja($this->stock, 0, 'dano_taller');
    }

    public function test_dar_de_baja_throws_on_insufficient_stock(): void
    {
        $service = new StockService;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No es posible dar de baja');
        $service->darDeBaja($this->stock, 15, 'dano_taller');
    }

    public function test_dar_de_baja_calculates_costo_total_perdida(): void
    {
        $service = new StockService;
        $baja = $service->darDeBaja($this->stock, 4, 'obsoleto');

        $this->assertEquals(120000, $baja->costo_total_perdida); // 4 * 30000
    }

    // ─── REVERTIR BAJA ───

    public function test_revertir_baja_restores_stock_and_deletes_baja(): void
    {
        $service = new StockService;
        $baja = $service->darDeBaja($this->stock, 3, 'dano_taller');
        $this->stock->refresh();
        $this->assertEquals(7, $this->stock->cantidad);

        $service->revertirBaja($baja);
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
        $this->assertDatabaseMissing('bajas_stock', ['id' => $baja->id]);
    }

    public function test_revertir_baja_accepts_baja_id(): void
    {
        $service = new StockService;
        $baja = $service->darDeBaja($this->stock, 2, 'dano_taller');
        $bajaId = $baja->id;

        $service->revertirBaja($bajaId);
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
        $this->assertDatabaseMissing('bajas_stock', ['id' => $bajaId]);
    }
}
