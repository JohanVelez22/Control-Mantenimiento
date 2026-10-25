<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $tecnico;

    private User $invitado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'active' => 1,
        ]);

        $this->tecnico = User::factory()->create([
            'role' => 'tecnico',
            'active' => 1,
        ]);

        $this->invitado = User::factory()->create([
            'role' => 'invitado',
            'active' => 1,
        ]);
    }

    public function test_admin_can_access_design_system_catalog(): void
    {
        $response = $this->actingAs($this->admin)->get(route('design-system'));

        $response->assertOk();
        $response->assertSee('Liquid Glass Design System');
        $response->assertSee('Tokens de Color Curados');
        $response->assertSee('Iconografía Vectorial SVG');
        $response->assertSee('demo-table');
        $response->assertSee('demo-ds-modal');
        $response->assertSee('https://wa.me/573001234567');
        $response->assertSee('Imprimir Catálogo');
    }

    public function test_tecnico_can_access_design_system_catalog(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('design-system'));

        $response->assertOk();
        $response->assertSee('Liquid Glass Design System');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('design-system'));

        $response->assertRedirect(route('login'));
    }

    public function test_invitado_is_redirected_away(): void
    {
        $response = $this->actingAs($this->invitado)->get(route('design-system'));

        $response->assertRedirect(route('dashboard'));
    }
}
