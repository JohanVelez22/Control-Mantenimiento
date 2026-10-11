<?php

namespace Tests\Feature;

use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicaStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected Electronica $electronica;
    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnicoM = Tecnico::factory()->create();

        $this->electronica = Electronica::factory()->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
            'estado' => 'pendiente',
        ]);

        $this->stock = Stock::factory()->create([
            'cantidad' => 10,
            'precio_venta' => 50000,
            'precio_tecnico' => 40000,
            'active' => true,
        ]);
    }

    public function test_admin_can_attach_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);
        $this->assertDatabaseHas('electronica_stock', [
            'electronica_id' => $this->electronica->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
        ]);
    }

    public function test_tecnico_can_attach_stock(): void
    {
        $response = $this->actingAs($this->tecnico)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->cantidad);
    }

    public function test_attach_stock_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => '',
            'cantidad' => '',
        ]);

        $response->assertSessionHasErrors(['stock_id', 'cantidad']);
    }

    public function test_attach_stock_rejects_invalid_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => 99999,
            'cantidad' => 1,
        ]);

        $response->assertSessionHasErrors('stock_id');
    }

    public function test_attach_stock_rejects_zero_cantidad(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 0,
        ]);

        $response->assertSessionHasErrors('cantidad');
    }

    public function test_attach_stock_rejects_more_than_available(): void
    {
        $this->stock->cantidad = 1;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 5,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('insuficiente', $response->getSession()->get('error'));
    }

    public function test_attach_stock_sums_quantity_if_already_exists(): void
    {
        $this->electronica->stocks()->attach($this->stock->id, [
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 3,
        ]);

        $response->assertRedirect();
        $pivot = $this->electronica->stocks()->where('stock_id', $this->stock->id)->first();
        $this->assertEquals(5, $pivot->pivot->cantidad);
    }

    public function test_admin_can_detach_stock(): void
    {
        $this->electronica->stocks()->attach($this->stock->id, [
            'cantidad' => 3,
            'precio_unitario' => 50000,
        ]);
        // Simular decremento del stock
        $this->stock->cantidad = 7;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->delete(route('electronicas.stocks.destroy', [$this->electronica, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
        $this->assertDatabaseMissing('electronica_stock', [
            'electronica_id' => $this->electronica->id,
            'stock_id' => $this->stock->id,
        ]);
    }

    public function test_tecnico_requires_admin_password_to_detach_stock(): void
    {
        $this->electronica->stocks()->attach($this->stock->id, [
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);
        $this->stock->cantidad = 8;
        $this->stock->save();

        $response = $this->actingAs($this->tecnico)->delete(route('electronicas.stocks.destroy', [$this->electronica, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_detach_stock_requires_admin_password(): void
    {
        $this->electronica->stocks()->attach($this->stock->id, [
            'cantidad' => 1,
            'precio_unitario' => 50000,
        ]);

        // Admin doesn't need password, test with tecnico
        $response = $this->actingAs($this->tecnico)->delete(route('electronicas.stocks.destroy', [$this->electronica, $this->stock->id]), []);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('contraseña', $response->getSession()->get('error'));
    }

    public function test_cannot_attach_stock_to_anulled_electronica(): void
    {
        $this->electronica->anulado = true;
        $this->electronica->save();

        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $this->electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_detach_stock_from_anulled_electronica(): void
    {
        $this->electronica->anulado = true;
        $this->electronica->save();

        $this->electronica->stocks()->attach($this->stock->id, [
            'cantidad' => 1,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('electronicas.stocks.destroy', [$this->electronica, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}