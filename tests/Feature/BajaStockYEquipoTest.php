<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Equipo;
use App\Models\BajaStock;
use App\Models\MovimientoCaja;
use App\Services\StockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BajaStockYEquipoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Stock $stock;
    protected Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->admin = User::first() ?? User::create([
            'name' => 'Admin Test',
            'email' => 'admintest_baja@tecnisystemas.com',
            'password' => bcrypt('Admin123*'),
            'role' => 'admin',
            'active' => true,
        ]);

        $this->stock = Stock::create([
            'codigo' => 'TEST-BAJA-SSD',
            'producto' => 'Disco Solido 240GB Prueba',
            'cantidad' => 10,
            'precio_compra' => 80000,
            'precio_venta' => 120000,
            'active' => true,
        ]);

        $this->equipo = Equipo::create([
            'nombre' => 'Laptop HP Pavilion Test',
            'marca' => 'HP',
            'modelo' => 'Pavilion 15',
            'serie' => 'HP-TEST-9988',
            'estado' => 'operativo',
            'active' => true,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_dar_de_baja_unidades_stock_descuenta_cantidad_y_crea_auditoria()
    {
        $stockService = app(StockService::class);
        $baja = $stockService->darDeBaja(
            $this->stock,
            2,
            'dano_taller',
            'Se dañó conector sata en mesa de pruebas',
            $this->admin->id
        );

        $this->stock->refresh();

        // 10 - 2 = 8
        $this->assertEquals(8, $this->stock->cantidad);
        $this->assertEquals(2, $baja->cantidad);
        $this->assertEquals(80000, $baja->precio_compra_unitario);
        $this->assertEquals(160000, $baja->costo_total_perdida); // 2 * 80000
        $this->assertEquals('dano_taller', $baja->motivo);
        $this->assertEquals(2, $this->stock->total_unidades_baja);
        $this->assertEquals(160000, $this->stock->total_perdida_bajas);
    }

    public function test_dar_de_baja_stock_mas_unidades_de_las_disponibles_lanza_excepcion()
    {
        $this->expectException(\DomainException::class);
        $stockService = app(StockService::class);
        $stockService->darDeBaja($this->stock, 15, 'perdida_merma');
    }

    public function test_dar_de_baja_stock_no_crea_movimientos_en_caja_chica()
    {
        $cajaCountAntes = MovimientoCaja::count();

        $stockService = app(StockService::class);
        $stockService->darDeBaja($this->stock, 1, 'defectuoso_fabrica', 'Chip memoria defectuoso', $this->admin->id);

        $cajaCountDespues = MovimientoCaja::count();

        // Garantía de riesgo cero en Caja
        $this->assertEquals($cajaCountAntes, $cajaCountDespues);
    }

    public function test_dar_de_baja_equipo_actualiza_estado_y_lo_excluye_de_activos()
    {
        $this->actingAs($this->admin);

        $this->assertTrue(Equipo::activos()->where('id', $this->equipo->id)->exists());

        $this->equipo->update([
            'estado' => 'dado_de_baja',
            'active' => false,
            'motivo_baja' => 'irreparable',
            'observacion_baja' => 'Placa base partida con cortos severos',
            'fecha_baja' => now(),
            'baja_user_id' => $this->admin->id,
        ]);

        $this->equipo->refresh();

        $this->assertEquals('dado_de_baja', $this->equipo->estado);
        $this->assertFalse($this->equipo->active);
        $this->assertTrue($this->equipo->esta_dado_de_baja);
        $this->assertEquals('Daño irreparable / Irrecuperable', $this->equipo->motivo_baja_label);

        // Ya NO debe aparecer en el scope de activos para nuevas órdenes
        $this->assertFalse(Equipo::activos()->where('id', $this->equipo->id)->exists());
    }

    public function test_reactivar_equipo_restaura_estado_operativo()
    {
        $this->equipo->update([
            'estado' => 'dado_de_baja',
            'active' => false,
            'motivo_baja' => 'irreparable',
        ]);

        // Simular reactivación
        $this->equipo->update([
            'estado' => 'operativo',
            'active' => true,
            'motivo_baja' => null,
            'observacion_baja' => null,
            'fecha_baja' => null,
            'baja_user_id' => null,
        ]);

        $this->equipo->refresh();

        $this->assertEquals('operativo', $this->equipo->estado);
        $this->assertTrue($this->equipo->active);
        $this->assertFalse($this->equipo->esta_dado_de_baja);
        $this->assertTrue(Equipo::activos()->where('id', $this->equipo->id)->exists());
    }

    public function test_http_dar_de_baja_stock_con_password()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('stocks.dar-de-baja', $this->stock->id), [
            'cantidad'         => 3,
            'motivo'           => 'obsoleto',
            'observacion'      => 'Deterioro por almacenamiento prolongado',
            'password_confirm' => 'Admin123*',
        ]);

        $response->assertSessionHas('success');
        $this->stock->refresh();
        $this->assertEquals(7, $this->stock->cantidad);
        $this->assertDatabaseHas('bajas_stock', [
            'stock_id' => $this->stock->id,
            'cantidad' => 3,
            'motivo'   => 'obsoleto',
        ]);
    }

    public function test_http_dar_de_baja_equipo_y_reactivar()
    {
        $this->actingAs($this->admin);

        // 1. Dar de baja
        $response = $this->post(route('equipos.dar-de-baja', $this->equipo->id), [
            'motivo_baja'      => 'irreparable',
            'observacion_baja' => 'Falla en CPU irrecuperable',
            'password_confirm' => 'Admin123*',
        ]);

        $response->assertSessionHas('success');
        $this->equipo->refresh();
        $this->assertEquals('dado_de_baja', $this->equipo->estado);
        $this->assertFalse($this->equipo->active);

        // 2. Reactivar
        $responseReactivar = $this->post(route('equipos.reactivar', $this->equipo->id), [
            'password_confirm' => 'Admin123*',
        ]);

        $responseReactivar->assertSessionHas('success');
        $this->equipo->refresh();
        $this->assertEquals('operativo', $this->equipo->estado);
        $this->assertTrue($this->equipo->active);
    }

    public function test_revertir_baja_stock_reintegra_unidades_y_elimina_merma()
    {
        $stockService = app(StockService::class);
        $baja = $stockService->darDeBaja($this->stock, 4, 'dano_taller', 'Prueba daño');
        $this->stock->refresh();
        $this->assertEquals(6, $this->stock->cantidad); // 10 - 4 = 6

        // Revertir
        $stockService->revertirBaja($baja);
        $this->stock->refresh();

        // Existencias restablecidas
        $this->assertEquals(10, $this->stock->cantidad);
        $this->assertDatabaseMissing('bajas_stock', ['id' => $baja->id]);
    }

    public function test_http_revertir_baja_stock_con_password()
    {
        $this->actingAs($this->admin);
        $stockService = app(StockService::class);
        $baja = $stockService->darDeBaja($this->stock, 3, 'obsoleto', 'Deterioro');
        $this->stock->refresh();
        $this->assertEquals(7, $this->stock->cantidad);

        $response = $this->post(route('stocks.bajas.revertir', $baja->id), [
            'password_confirm' => 'Admin123*',
        ]);

        $response->assertSessionHas('success');
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
        $this->assertDatabaseMissing('bajas_stock', ['id' => $baja->id]);
    }

    public function test_admin_puede_dar_de_baja_y_revertir_sin_password()
    {
        $this->actingAs($this->admin);

        // 1. Dar de baja sin password_confirm
        $response = $this->post(route('stocks.dar-de-baja', $this->stock->id), [
            'cantidad'    => 2,
            'motivo'      => 'obsoleto',
            'observacion' => 'Baja directa por admin sin clave',
        ]);

        $response->assertSessionHas('success');
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);

        $baja = BajaStock::where('stock_id', $this->stock->id)->first();
        $this->assertNotNull($baja);

        // 2. Revertir sin password_confirm
        $responseRevertir = $this->post(route('stocks.bajas.revertir', $baja->id), []);
        $responseRevertir->assertSessionHas('success');
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_tecnico_requiere_password_de_administrador_para_dar_de_baja()
    {
        $tecnico = User::create([
            'name'     => 'Tecnico Test',
            'email'    => 'tecnico_baja_test_' . uniqid() . '@tecnisystemas.com',
            'password' => bcrypt('Tecnico123*'),
            'role'     => 'tecnico',
            'active'   => true,
        ]);

        $this->actingAs($tecnico);

        // Intento 1: Sin clave -> Falla
        $responseSinClave = $this->post(route('stocks.dar-de-baja', $this->stock->id), [
            'cantidad'    => 1,
            'motivo'      => 'dano_taller',
            'observacion' => 'Intento sin clave',
        ]);
        $responseSinClave->assertSessionHas('error');

        // Intento 2: Con su propia clave de técnico (no de admin) -> Falla
        $responseClaveTecnico = $this->post(route('stocks.dar-de-baja', $this->stock->id), [
            'cantidad'         => 1,
            'motivo'           => 'dano_taller',
            'observacion'      => 'Intento con clave incorrecta',
            'password_confirm' => 'Tecnico123*',
        ]);
        $responseClaveTecnico->assertSessionHas('error');

        // Intento 3: Con clave válida de administrador -> Éxito
        $responseClaveAdmin = $this->post(route('stocks.dar-de-baja', $this->stock->id), [
            'cantidad'         => 1,
            'motivo'           => 'dano_taller',
            'observacion'      => 'Intento con clave admin',
            'password_confirm' => 'Admin123*',
        ]);
        $responseClaveAdmin->assertSessionHas('success');
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->cantidad);
    }
}
