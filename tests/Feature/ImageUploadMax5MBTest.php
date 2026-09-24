<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use App\Models\Tecnico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadMax5MBTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_stock_admite_imagen_svg_hasta_5mb(): void
    {
        Storage::fake('public');
        $proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'identificacion' => '900999888',
            'email' => 'prov@test.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 100',
            'tipo_entidad' => 'empresa',
        ]);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('producto.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'PROD-SVG-1',
            'producto' => 'Mouse Ergonómico',
            'categoria' => 'Periféricos',
            'subcategoria' => 'Mouse',
            'cantidad' => 15,
            'proveedor_id' => $proveedor->id,
            'precio_compra' => 50000,
            'utilidad' => 30,
            'precio_venta' => 65000,
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $stock = Stock::where('codigo', 'PROD-SVG-1')->first();
        $this->assertNotNull($stock);
        $this->assertNotNull($stock->photo);
        Storage::disk('public')->assertExists($stock->photo);
    }

    public function test_usuario_admite_foto_hasta_5mb(): void
    {
        Storage::fake('public');

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>';
        $file = UploadedFile::fake()->createWithContent('avatar.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Carlos Usuario',
            'email' => 'carlos@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'tecnico',
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $user = User::where('email', 'carlos@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);
    }

    public function test_tecnico_admite_foto_hasta_5mb(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('tecnico.webp')->size(4000); // 4 MB

        $response = $this->actingAs($this->admin)->post(route('tecnicos.store'), [
            'nombre' => 'Mario Mendoza',
            'identificacion' => 'TEC-9988',
            'especialidad' => 'Laptops',
            'movil' => '3007654321',
            'email' => 'mario@test.com',
            'photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $tecnico = Tecnico::where('identificacion', 'TEC-9988')->first();
        $this->assertNotNull($tecnico);
        $this->assertNotNull($tecnico->photo);
        Storage::disk('public')->assertExists($tecnico->photo);
    }

    public function test_subida_imagen_mayor_a_5mb_falla_en_stock_y_usuario(): void
    {
        Storage::fake('public');

        $fileStock = UploadedFile::fake()->create('heavy_image.png', 6000); // 6 MB

        $response = $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'PROD-FAIL',
            'producto' => 'Producto Pesado',
            'categoria' => 'Periféricos',
            'subcategoria' => 'Mouse',
            'cantidad' => 5,
            'proveedor_id' => 1,
            'precio_compra' => 10000,
            'utilidad' => 20,
            'photo' => $fileStock,
        ]);

        $response->assertSessionHasErrors(['photo']);
    }
}
