<?php

namespace Tests\Feature;

use App\Models\CategoriaStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaStockControllerTest extends TestCase
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

    public function test_admin_can_view_categorias_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('stocks.categorias.index'));
        $response->assertStatus(200);
        $response->assertSee('Categorías de Stock');
    }

    public function test_tecnico_can_view_categorias_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('stocks.categorias.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_categoria(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stocks.categorias.store'), [
            'nombre' => 'Nueva Categoría',
            'tipo' => 'categoria',
        ]);

        $response->assertRedirect(route('stocks.categorias.index'));
        $this->assertDatabaseHas('categoria_stocks', [
            'nombre' => 'Nueva Categoría',
            'tipo' => 'categoria',
        ]);
    }

    public function test_categoria_creation_validates_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('stocks.categorias.store'), [
            'nombre' => '',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_categoria_creation_rejects_duplicate_nombre(): void
    {
        CategoriaStock::create([
            'nombre' => 'Duplicada',
            'tipo' => 'categoria',
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.categorias.store'), [
            'nombre' => 'Duplicada',
            'tipo' => 'categoria',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_admin_can_edit_categoria(): void
    {
        $categoria = CategoriaStock::create([
            'nombre' => 'Original',
            'tipo' => 'categoria',
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.categorias.edit', $categoria));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_categoria(): void
    {
        $categoria = CategoriaStock::create([
            'nombre' => 'Original',
            'tipo' => 'categoria',
        ]);

        $response = $this->actingAs($this->admin)->put(route('stocks.categorias.update', $categoria), [
            'nombre' => 'Actualizada',
            'tipo' => 'subcategoria',
        ]);

        $response->assertRedirect(route('stocks.categorias.index'));
        $categoria->refresh();
        $this->assertEquals('Actualizada', $categoria->nombre);
        $this->assertEquals('subcategoria', $categoria->tipo);
    }

    public function test_admin_can_delete_categoria(): void
    {
        $categoria = CategoriaStock::create([
            'nombre' => 'Para Eliminar',
            'tipo' => 'categoria',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('stocks.categorias.destroy', $categoria));
        $response->assertRedirect(route('stocks.categorias.index'));
        $this->assertDatabaseMissing('categoria_stocks', ['id' => $categoria->id]);
    }

    public function test_categoria_with_subcategorias_shows_hierarchy(): void
    {
        $categoria = CategoriaStock::create([
            'nombre' => 'Hardware',
            'tipo' => 'categoria',
        ]);

        $subcategoria = CategoriaStock::create([
            'nombre' => 'Memorias',
            'tipo' => 'subcategoria',
            'parent_id' => $categoria->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('stocks.categorias.index'));
        $response->assertStatus(200);
        $response->assertSee('Hardware');
        $response->assertSee('Memorias');
    }
}