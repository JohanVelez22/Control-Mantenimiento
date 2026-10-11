<?php

namespace Tests\Feature;

use App\Models\ConceptoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConceptoCajaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);
    }

    public function test_admin_can_view_conceptos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('conceptos.index'));
        $response->assertStatus(200);
        $response->assertSee('Conceptos de Caja');
    }

    public function test_tecnico_can_view_conceptos_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('conceptos.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_concepto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('conceptos.store'), [
            'nombre' => 'Nuevo Concepto',
        ]);

        $response->assertRedirect(route('conceptos.index'));
        $this->assertDatabaseHas('concepto_cajas', ['nombre' => 'Nuevo Concepto']);
    }

    public function test_concepto_creation_validates_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('conceptos.store'), [
            'nombre' => '',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_concepto_creation_rejects_duplicate(): void
    {
        ConceptoCaja::create(['nombre' => 'Duplicado']);

        $response = $this->actingAs($this->admin)->post(route('conceptos.store'), [
            'nombre' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_admin_can_edit_concepto(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)->get(route('conceptos.edit', $concepto));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_concepto(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)->put(route('conceptos.update', $concepto), [
            'nombre' => 'Actualizado',
        ]);

        $response->assertRedirect(route('conceptos.index'));
        $concepto->refresh();
        $this->assertEquals('Actualizado', $concepto->nombre);
    }

    public function test_admin_can_delete_concepto(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Para Eliminar']);

        $response = $this->actingAs($this->admin)->delete(route('conceptos.destroy', $concepto));
        $response->assertRedirect(route('conceptos.index'));
        $this->assertDatabaseMissing('concepto_cajas', ['id' => $concepto->id]);
    }
}