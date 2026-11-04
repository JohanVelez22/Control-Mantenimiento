<?php

namespace Tests\Feature;

use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendAccessibilityAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * F-05: Modales globales deben incluir roles y atributos de accesibilidad ARIA (WCAG 2.1).
     */
    public function test_modales_globales_tienen_atributos_aria_en_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // ts-modal
        $response->assertSee('id="ts-modal"', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-labelledby="ts-modal-title"', false);

        // global-anular-modal
        $response->assertSee('id="global-anular-modal"', false);
        $response->assertSee('aria-labelledby="global-anular-title"', false);

        // global-rechazar-modal
        $response->assertSee('id="global-rechazar-modal"', false);
        $response->assertSee('aria-labelledby="global-rechazar-title"', false);

        // image-lightbox
        $response->assertSee('id="image-lightbox"', false);
        $response->assertSee('aria-label="Vista previa de imagen"', false);
    }

    /**
     * F-09: Verificación de etiquetas preconnect para fuentes tipográficas.
     */
    public function test_layout_incluye_preconnect_para_fuentes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('<link rel="preconnect" href="https://fonts.googleapis.com">', false);
        $response->assertSee('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>', false);
    }

    /**
     * F-04: Las imágenes de perfiles y stocks usan data-lightbox-src y no onclick con addslashes.
     */
    public function test_imagenes_usan_atributos_data_lightbox_sin_addslashes(): void
    {
        $user = User::factory()->create([
            'name' => "User O'Connor \"Admin\"",
            'photo' => 'usuarios/test.png',
        ]);

        $responseUser = $this->actingAs($this->admin)->get(route('usuarios.index'));
        $responseUser->assertStatus(200);
        $responseUser->assertSee('data-lightbox-src=', false);
        $responseUser->assertDontSee('addslashes(', false);

        $tecnico = Tecnico::factory()->create([
            'nombre' => "Técnico D'Angelo",
            'photo' => 'tecnicos/test.png',
        ]);

        $responseTecnico = $this->actingAs($this->admin)->get(route('tecnicos.index'));
        $responseTecnico->assertStatus(200);
        $responseTecnico->assertSee('data-lightbox-src=', false);

        $stock = Stock::create([
            'codigo' => 'DISC-TEST',
            'producto' => 'Disco SSD 500GB "Crucial"',
            'categoria' => 'Discos',
            'cantidad' => 5,
            'precio_compra' => 100000,
            'precio_venta' => 150000,
            'active' => true,
            'photo' => 'stocks/test.png',
        ]);

        $responseStock = $this->actingAs($this->admin)->get(route('stocks.index'));
        $responseStock->assertStatus(200);
        $responseStock->assertSee('data-lightbox-src=', false);
    }

    /**
     * F-02: Verificación sintáctica de que glass.css no contiene declaraciones huérfanas en @media print.
     */
    public function test_glass_css_no_contiene_declaraciones_huerfanas_en_print(): void
    {
        $cssContent = str_replace("\r\n", "\n", file_get_contents(public_path('css/glass.css')));
        $this->assertNotEmpty($cssContent);

        // No debe existir el bloque sin selector th, td
        $this->assertStringNotContainsString(
            "10. Colores forzados en encabezados de tabla\n   ================================================================ */\n  -webkit-print-color-adjust:",
            $cssContent
        );

        $this->assertStringContainsString("th, td {\n    -webkit-print-color-adjust: exact !important;", $cssContent);
    }
}
