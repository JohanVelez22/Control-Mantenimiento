<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventosViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_eventos_with_homogeneous_selects()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        Evento::create([
            'user_id' => $admin->id,
            'accion' => 'login',
            'descripcion' => 'Inicio de sesión exitoso',
            'modelo_tipo' => 'User',
            'modelo_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('eventos.index'));

        $response->assertStatus(200);
        $response->assertSee('Registro de Eventos');
        // Both accion and user_id should use glass-input w-40 text-sm and not no-search
        $response->assertSee('name="accion" class="glass-input w-40 text-sm"', false);
        $response->assertSee('name="user_id" class="glass-input w-40 text-sm"', false);
        $response->assertDontSee('name="accion" class="glass-input no-search', false);
    }

    public function test_filtering_by_accion_and_user_works()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        Evento::create([
            'user_id' => $admin->id,
            'accion' => 'login',
            'descripcion' => 'Inicio de sesión test',
            'modelo_tipo' => 'User',
            'modelo_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('eventos.index', [
            'accion' => 'login',
            'user_id' => $admin->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Inicio de sesión test');
    }

    public function test_venta_and_compra_views_have_searchable_stock_selects()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        // Lo importante es que el selector de producto sea buscable (TomSelect se aplica a
        // .glass-input salvo que tenga .no-search), no el orden exacto de las clases.
        foreach (['inventario.venta.create', 'inventario.compra.create'] as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertStatus(200)->getContent();

            $this->assertMatchesRegularExpression('/<select name="items\[0\]\[stock_id\]"[^>]*class="[^"]*\bstock-select\b[^"]*\bglass-input\b[^"]*"/', $html, "{$ruta}: falta el selector de producto");
            $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bstock-select\b[^"]*\bno-search\b/', $html, "{$ruta}: el selector de producto no debe excluir la búsqueda");
        }
    }

    public function test_clientes_and_proveedores_views_have_searchable_selects()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $resCli = $this->actingAs($admin)->get(route('clientes.create'));
        $resCli->assertStatus(200);
        $resCli->assertDontSee('name="genero" required class="glass-input no-search"', false);
        $resCli->assertSee('name="genero" required class="glass-input"', false);
        $resCli->assertDontSee('id="select_departamento" class="glass-input no-search"', false);
        $resCli->assertSee('id="select_departamento" class="glass-input"', false);

        $resProv = $this->actingAs($admin)->get(route('proveedores.create'));
        $resProv->assertStatus(200);
        $resProv->assertDontSee('name="tipo_entidad" required class="glass-input no-search', false);
        $resProv->assertSee('name="tipo_entidad" required class="glass-input', false);
        $resProv->assertDontSee('id="prov_departamento" class="glass-input no-search', false);
        $resProv->assertSee('id="prov_departamento" class="glass-input', false);
    }
}
