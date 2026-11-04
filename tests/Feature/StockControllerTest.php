<?php

namespace Tests\Feature;

use App\Models\BajaStock;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'identificacion' => '900123456',
            'tipo_entidad' => 'empresa',
            'email' => 'prov@test.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 100',
        ]);
    }

    public function test_admin_can_view_stock_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stocks.index'));
        $response->assertStatus(200);
        $response->assertSee('Inventario');
    }

    public function test_tecnico_can_view_stock_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('stocks.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_stock_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('stocks.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_stock(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-001',
            'producto' => 'Mouse Inalámbrico',
            'categoria' => 'Periféricos',
            'subcategoria' => 'Mouse',
            'cantidad' => 20,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 30000,
            'utilidad' => 50,
            'precio_venta' => 45000,
        ]);

        $response->assertRedirect(route('stocks.index'));
        $this->assertDatabaseHas('stocks', [
            'codigo' => 'STK-001',
            'producto' => 'Mouse Inalámbrico',
            'cantidad' => 20,
        ]);
    }

    public function test_stock_creation_auto_calculates_precio_venta(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-AUTO-1',
            'producto' => 'Auto Precio Venta',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 30,
        ]);

        $response->assertRedirect();
        $stock = Stock::where('codigo', 'STK-AUTO-1')->first();
        $this->assertNotNull($stock);
        $this->assertEquals(130000, $stock->precio_venta);
    }

    public function test_stock_creation_auto_calculates_precio_tecnico(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-AUTO-2',
            'producto' => 'Auto Precio Tecnico',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 100000,
            'utilidad' => 40,
        ]);

        $response->assertRedirect();
        $stock = Stock::where('codigo', 'STK-AUTO-2')->first();
        $this->assertNotNull($stock);
        $this->assertEquals(120000, $stock->precio_tecnico);
    }

    public function test_stock_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'producto' => 'Sin Código',
        ]);

        $response->assertSessionHasErrors(['categoria', 'subcategoria', 'cantidad', 'proveedor_id', 'precio_compra', 'utilidad']);
    }

    public function test_stock_creation_rejects_duplicate_codigo(): void
    {
        Stock::create([
            'codigo' => 'STK-DUP',
            'producto' => 'Original',
            'categoria' => 'Test',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 10000,
            'utilidad' => 20,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-DUP',
            'producto' => 'Duplicado',
            'categoria' => 'Test',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 10000,
            'utilidad' => 20,
        ]);

        $response->assertSessionHasErrors(['codigo']);
    }

    public function test_admin_can_edit_stock(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-EDIT-1',
            'producto' => 'Producto Original',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'precio_venta' => 70000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.edit', $stock));
        $response->assertStatus(200);
        $response->assertSee('STK-EDIT-1');
    }

    public function test_admin_can_update_stock(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-UPD-1',
            'producto' => 'Producto Original',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'precio_venta' => 70000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('stocks.update', $stock), [
            'codigo' => 'STK-UPD-1',
            'producto' => 'Producto Actualizado',
            'categoria' => 'Test Actualizado',
            'subcategoria' => 'Sub Actualizada',
            'cantidad' => 15,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 60000,
            'utilidad' => 50,
            'precio_venta' => 90000,
        ]);

        $response->assertRedirect(route('stocks.index'));
        $stock->refresh();
        $this->assertEquals('Producto Actualizado', $stock->producto);
        $this->assertEquals('Test Actualizado', $stock->categoria);
        $this->assertEquals(15, $stock->cantidad);
        $this->assertEquals(60000, $stock->precio_compra);
        $this->assertEquals(50, $stock->utilidad);
        $this->assertEquals(90000, $stock->precio_venta);
    }

    public function test_admin_can_anular_stock(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-ANUL-1',
            'producto' => 'Para Anular',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.anular', $stock), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $stock->refresh();
        $this->assertFalse($stock->active);
    }

    public function test_admin_can_reactivar_stock(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-REACT-1',
            'producto' => 'Para Reactivar',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.anular', $stock), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $stock->refresh();
        $this->assertTrue($stock->active);
    }

    public function test_tecnico_requires_admin_password_to_anular_stock(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-TEC-ANUL-1',
            'producto' => 'Tecnico Anular',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'active' => true,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('stocks.anular', $stock), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $stock->refresh();
        $this->assertFalse($stock->active);
    }

    public function test_stock_dar_de_baja(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-BAJA-1',
            'producto' => 'Para Baja',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.dar-de-baja', $stock), [
            'cantidad' => 3,
            'motivo' => 'dano_taller',
            'observacion' => 'Se dañó en pruebas',
            'password_confirm' => 'password',
        ]);

        $response->assertSessionHas('success');
        $stock->refresh();
        $this->assertEquals(7, $stock->cantidad);
        $this->assertEquals(3, $stock->total_unidades_baja);
        $this->assertEquals(240000, $stock->total_perdida_bajas);
    }

    public function test_stock_dar_de_baja_rejects_more_than_available(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-BAJA-2',
            'producto' => 'Para Baja Exceso',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.dar-de-baja', $stock), [
            'cantidad' => 10,
            'motivo' => 'dano_taller',
            'password_confirm' => 'password',
        ]);

        $response->assertSessionHasErrors('cantidad');
    }

    public function test_stock_revertir_baja(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-REV-1',
            'producto' => 'Para Revertir',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'active' => true,
        ]);

        // Create baja via controller to properly decrement stock
        $response = $this->actingAs($this->admin)->post(route('stocks.dar-de-baja', $stock), [
            'cantidad' => 4,
            'motivo' => 'dano_taller',
            'password_confirm' => 'password',
        ]);

        $response->assertSessionHas('success');
        $stock->refresh();
        $this->assertEquals(6, $stock->cantidad);

        $baja = BajaStock::where('stock_id', $stock->id)->first();
        $this->assertNotNull($baja);

        $response = $this->actingAs($this->admin)->post(route('stocks.bajas.revertir', $baja), [
            'password_confirm' => 'password',
        ]);

        $response->assertSessionHas('success');
        $stock->refresh();
        $this->assertEquals(10, $stock->cantidad);
        $this->assertDatabaseMissing('bajas_stock', ['id' => $baja->id]);
    }

    public function test_stock_print_view(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-PRINT-1',
            'producto' => 'Para Imprimir',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
            'precio_venta' => 70000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.print', $stock));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_stock_reportes_export_excel(): void
    {
        Stock::create([
            'codigo' => 'STK-EXCEL-1',
            'producto' => 'Export Test',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.reportes', ['export' => 'excel']));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_stock_reportes_export_pdf(): void
    {
        Stock::create([
            'codigo' => 'STK-PDF-1',
            'producto' => 'Export Test',
            'categoria' => 'Test',
            'cantidad' => 10,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 40,
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.reportes', ['export' => 'pdf']));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_stock_image_upload_rejects_svg(): void
    {
        Storage::fake('public');

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('producto.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-SVG-1',
            'producto' => 'Con SVG',
            'categoria' => 'Test',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 10000,
            'utilidad' => 20,
            'photo' => $file,
        ]);

        $response->assertSessionHasErrors(['photo']);
        $this->assertNull(Stock::where('codigo', 'STK-SVG-1')->first());
    }

    public function test_stock_image_upload_accepts_png_under_5mb(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('producto.png')->size(4000);

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-PNG-1',
            'producto' => 'Con PNG',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 10000,
            'utilidad' => 20,
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $stock = Stock::where('codigo', 'STK-PNG-1')->first();
        $this->assertNotNull($stock);
        $this->assertNotNull($stock->photo);
    }
}
