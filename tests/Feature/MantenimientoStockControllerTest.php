<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantenimientoStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected Mantenimiento $mantenimiento;

    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $cliente = Cliente::factory()->create(['tipo_cliente' => 'cliente']);
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnicoM = Tecnico::factory()->create();

        $this->mantenimiento = Mantenimiento::factory()->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
            'estado' => 'pendiente',
            'costo' => 0,
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
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);
        $this->assertDatabaseHas('mantenimiento_stock', [
            'mantenimiento_id' => $this->mantenimiento->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
        ]);
        $this->mantenimiento->refresh();
        $this->assertEquals(100000, $this->mantenimiento->costo);
    }

    public function test_tecnico_can_attach_stock(): void
    {
        $response = $this->actingAs($this->tecnico)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->cantidad);
    }

    public function test_attach_stock_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => '',
            'cantidad' => '',
        ]);

        $response->assertSessionHasErrors(['stock_id', 'cantidad']);
    }

    public function test_attach_stock_rejects_invalid_stock(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => 99999,
            'cantidad' => 1,
        ]);

        $response->assertSessionHasErrors('stock_id');
    }

    public function test_attach_stock_rejects_zero_cantidad(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 0,
        ]);

        $response->assertSessionHasErrors('cantidad');
    }

    public function test_attach_stock_rejects_more_than_available(): void
    {
        $this->stock->cantidad = 1;
        $this->stock->save();

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 5,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('insuficiente', $response->getSession()->get('error'));
    }

    public function test_attach_stock_sums_quantity_if_already_exists(): void
    {
        // Use controller to attach initial stock so costo is updated correctly
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 2,
        ]);
        $response->assertRedirect();
        $this->mantenimiento->refresh();
        $initialCosto = $this->mantenimiento->costo; // Should be 100000

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 3,
        ]);

        $response->assertRedirect();
        $pivot = $this->mantenimiento->stocks()->where('stock_id', $this->stock->id)->first();
        $this->assertEquals(5, $pivot->pivot->cantidad);
        $this->mantenimiento->refresh();
        // Initial 2 units + 3 new units = 5 units at 50000 each = 250000
        $this->assertEquals($initialCosto + 150000, $this->mantenimiento->costo);
    }

    public function test_admin_can_detach_stock(): void
    {
        // Use controller to attach initial stock so costo is updated correctly
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 3,
        ]);
        $response->assertRedirect();
        $this->mantenimiento->refresh();
        $initialCosto = $this->mantenimiento->costo; // Should be 150000
        $this->stock->refresh(); // Stock should be 7 after decrement

        $response = $this->actingAs($this->admin)->delete(route('mantenimientos.stocks.destroy', [$this->mantenimiento, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
        $this->mantenimiento->refresh();
        $this->assertEquals(0, $this->mantenimiento->costo);
    }

    public function test_tecnico_requires_admin_password_to_detach_stock(): void
    {
        $this->mantenimiento->stocks()->attach($this->stock->id, [
            'cantidad' => 2,
            'precio_unitario' => 50000,
        ]);
        $this->stock->cantidad = 8;
        $this->stock->save();

        $response = $this->actingAs($this->tecnico)->delete(route('mantenimientos.stocks.destroy', [$this->mantenimiento, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_detach_stock_requires_admin_password(): void
    {
        $this->mantenimiento->stocks()->attach($this->stock->id, [
            'cantidad' => 1,
            'precio_unitario' => 50000,
        ]);

        // Admin doesn't need password, test with tecnico
        $response = $this->actingAs($this->tecnico)->delete(route('mantenimientos.stocks.destroy', [$this->mantenimiento, $this->stock->id]), []);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('contraseña', $response->getSession()->get('error'));
    }

    public function test_cannot_attach_stock_to_anulled_mantenimiento(): void
    {
        $this->mantenimiento->anulado = true;
        $this->mantenimiento->save();

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_detach_stock_from_anulled_mantenimiento(): void
    {
        $this->mantenimiento->anulado = true;
        $this->mantenimiento->save();

        $this->mantenimiento->stocks()->attach($this->stock->id, [
            'cantidad' => 1,
            'precio_unitario' => 50000,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('mantenimientos.stocks.destroy', [$this->mantenimiento, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_attach_stock_applies_precio_tecnico_for_tecnico_cliente(): void
    {
        $clienteTecnico = Cliente::factory()->create(['tipo_cliente' => 'tecnico']);
        $equipoTecnico = Equipo::factory()->create(['cliente_id' => $clienteTecnico->id]);
        $tecnicoM = Tecnico::factory()->create();

        $mantenimientoTecnico = Mantenimiento::factory()->create([
            'equipo_id' => $equipoTecnico->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
            'estado' => 'pendiente',
            'costo' => 0,
        ]);

        $stockTecnico = Stock::factory()->create([
            'cantidad' => 10,
            'precio_venta' => 50000,
            'precio_tecnico' => 40000,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $mantenimientoTecnico), [
            'stock_id' => $stockTecnico->id,
            'cantidad' => 2,
        ]);

        $response->assertRedirect();
        $pivot = $mantenimientoTecnico->stocks()->where('stock_id', $stockTecnico->id)->first();
        $this->assertEquals(40000, $pivot->pivot->precio_unitario);
    }
}
