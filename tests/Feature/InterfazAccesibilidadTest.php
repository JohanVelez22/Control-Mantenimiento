<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-08 a F-37: comportamiento de la interfaz que se puede comprobar en el HTML.
 */
class InterfazAccesibilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['name' => 'Ángela Ruiz', 'role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    public function test_layout_anuncia_mensajes_y_no_borra_los_placeholder(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="toast-container" role="status" aria-live="polite"', $html);
        $this->assertStringNotContainsString('savedPlaceholder', $html, 'F-10: el placeholder no se borra al enfocar');
        $this->assertStringNotContainsString("message.includes('cotización')", $html, 'F-19: el color del modal no se deduce del texto');
        $this->assertStringNotContainsString('pb-[30vh]', $html, 'F-23: sin media pantalla en blanco');
        $this->assertSame(0, substr_count($html, 'function initPasswordToggles'), 'F-26: una sola copia (en app.js)');
    }

    public function test_avatar_y_rol_bien_escritos(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('Á', $html, 'F-22: la inicial con tilde no se corta');
        $this->assertStringContainsString('Administrador', $html);
    }

    public function test_campana_y_menu_lateral_accesibles(): void
    {
        Mantenimiento::factory()->create(['anulado' => false, 'estado' => 'pendiente', 'fecha_entrada' => now()->subDays(10)->toDateString()]);

        $html = $this->actingAs($this->admin)->get(route('caja.index'))->getContent();

        $this->assertMatchesRegularExpression('/aria-label="Notificaciones: 1 pendientes, 1 atrasadas"/', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*\/caja"[^>]*aria-current="page"/', $html, 'F-25: la sección actual se anuncia');
        $this->assertStringContainsString('Compras y ventas', $html);
        $this->assertStringNotContainsString('Info Operativos', $html);
    }

    public function test_botones_de_icono_tienen_nombre_accesible(): void
    {
        $orden = Mantenimiento::factory()->create(['anulado' => false]);
        $html = $this->actingAs($this->admin)->get(route('mantenimientos.index'))->getContent();

        // Botones y enlaces cuyo contenido es solo un emoji (los enlaces del menú lateral tienen
        // texto visible y no lo necesitan).
        preg_match_all('/(<(?:button|a)\b[^>]*\btitle="[^"]*"[^>]*>)\s*(?:<span[^>]*>)?\s*[^\sA-Za-z0-9<]{1,4}\s*(?:<\/span>)?\s*<\/(?:button|a)>/u', $html, $etiquetas);
        $sinNombre = array_filter($etiquetas[1], fn ($t) => ! str_contains($t, 'aria-label'));
        $this->assertNotEmpty($etiquetas[1], 'La página tiene botones de ícono');
        $this->assertSame([], array_values($sinNombre), 'F-15: todo botón con title tiene aria-label');
        $this->assertNotEmpty($orden);
    }

    public function test_pestanas_del_panel_con_roles_aria(): void
    {
        $html = $this->actingAs($this->admin)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab" aria-selected="true" aria-controls="tabPanelMant"', $html);
        $this->assertStringContainsString('aria-label="Ir al gráfico 1"', $html);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $html);
    }
}
