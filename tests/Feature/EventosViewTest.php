<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Evento;

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

        $resVenta = $this->actingAs($admin)->get(route('inventario.venta.create'));
        $resVenta->assertStatus(200);
        $resVenta->assertDontSee('stock-select glass-input no-search', false);
        $resVenta->assertSee('stock-select glass-input py-1.5 focus:ring-emerald-500', false);

        $resCompra = $this->actingAs($admin)->get(route('inventario.compra.create'));
        $resCompra->assertStatus(200);
        $resCompra->assertDontSee('stock-select glass-input no-search', false);
        $resCompra->assertSee('stock-select glass-input py-1.5 focus:ring-orange-500', false);
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
