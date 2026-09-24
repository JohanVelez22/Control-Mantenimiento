<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_puede_subir_logo_svg_hasta_5mb(): void
    {
        Storage::fake('public');

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $response = $this->actingAs($this->admin)->post(route('configuracion.update'), [
            'nombre' => 'Tecni Systemas SAS',
            'nit' => '900123456-1',
            'logo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $configuracion = Configuracion::first();
        $this->assertNotNull($configuracion->logo_path);
        Storage::disk('public')->assertExists($configuracion->logo_path);
    }

    public function test_admin_puede_eliminar_logo_existente(): void
    {
        Storage::fake('public');

        $configuracion = Configuracion::create([
            'nombre' => 'Tecni Systemas SAS',
            'logo_path' => 'configuracion/test_logo.png',
        ]);
        Storage::disk('public')->put('configuracion/test_logo.png', 'dummy');

        $response = $this->actingAs($this->admin)->post(route('configuracion.update'), [
            'nombre' => 'Tecni Systemas SAS',
            'eliminar_logo' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $configuracion->refresh();
        $this->assertNull($configuracion->logo_path);
        Storage::disk('public')->assertMissing('configuracion/test_logo.png');
    }

    public function test_logo_mayor_a_5mb_falla_validacion(): void
    {
        Storage::fake('public');

        // 6 MB file
        $file = UploadedFile::fake()->create('logo_grande.png', 6144);

        $response = $this->actingAs($this->admin)->post(route('configuracion.update'), [
            'nombre' => 'Tecni Systemas SAS',
            'logo' => $file,
        ]);

        $response->assertSessionHasErrors(['logo']);
    }
}
