<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected Cliente $cliente;
    protected Equipo $equipo;
    protected Tecnico $tecnicoM;
    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'identificacion' => '12345678',
            'movil' => '3001234567',
            'email' => 'juan@test.com',
        ]);

        $this->equipo = Equipo::create([
            'nombre' => 'Laptop HP',
            'marca' => 'HP',
            'modelo' => 'Pavilion',
            'serie' => 'SN1234',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $this->tecnicoM = Tecnico::create([
            'nombre' => 'Pedro Técnico',
            'identificacion' => '88889999',
            'especialidad' => 'General',
            'movil' => '3009998877',
            'email' => 'pedro@test.com',
        ]);

        $this->stock = Stock::create([
            'codigo' => 'RAM-8GB',
            'producto' => 'Memoria RAM 8GB',
            'categoria' => 'Repuestos',
            'cantidad' => 10,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'precio_venta' => 120000,
            'active' => true,
        ]);
    }

    public function test_admin_can_create_electronica(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'No enciende',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('electronicas.index'));
        $this->assertDatabaseHas('electronicas', [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'No enciende',
        ]);
    }

    public function test_tecnico_can_create_electronica(): void
    {
        $response = $this->actingAs($this->tecnico)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Pantalla rota',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 200000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('electronicas.index'));
    }

    public function test_guest_cannot_create_electronica(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_electronica_creation_requires_valid_equipo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => 99999,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['equipo_id']);
    }

    public function test_electronica_creation_requires_valid_tipo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'invalido',
            'reparacion' => 'hardware',
            'costo' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['tipo']);
    }

    public function test_electronica_creation_requires_valid_reparacion(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'invalido',
            'costo' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['reparacion']);
    }

    public function test_electronica_creation_requires_valid_estado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 100000,
            'estado' => 'invalido',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['estado']);
    }

    public function test_electronica_creation_requires_valid_tecnico(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.store'), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => 99999,
        ]);

        $response->assertSessionHasErrors(['tecnico_id']);
    }

    public function test_admin_can_view_electronica_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('electronicas.index'));
        $response->assertStatus(200);
        $response->assertSee('Electrónica');
    }

    public function test_tecnico_can_view_electronica_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('electronicas.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_electronica_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('electronicas.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_electronica_show(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-001',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('electronicas.show', $electronica));
        $response->assertStatus(200);
        $response->assertSee('ELC-001');
    }

    public function test_admin_can_edit_electronica(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-002',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('electronicas.edit', $electronica));
        $response->assertStatus(200);
        $response->assertSee('ELC-002');
    }

    public function test_admin_can_update_electronica(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-003',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('electronicas.update', $electronica), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Actualizado',
            'tipo' => 'preventivo',
            'reparacion' => 'software',
            'costo' => 100000,
            'estado' => 'terminado',
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('electronicas.index'));
        $electronica->refresh();
        $this->assertEquals('Actualizado', $electronica->descripcion_problema);
        $this->assertEquals('preventivo', $electronica->tipo);
        $this->assertEquals('software', $electronica->reparacion);
        $this->assertEquals('terminado', $electronica->estado);
    }

    public function test_tecnico_can_update_electronica_with_admin_password(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-004',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->tecnico)->put(route('electronicas.update', $electronica), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Actualizado por tecnico',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'admin_password' => 'password',
        ]);

        $response->assertRedirect(route('electronicas.index'));
        $electronica->refresh();
        $this->assertEquals('Actualizado por tecnico', $electronica->descripcion_problema);
    }

    public function test_tecnico_cannot_update_electronica_without_admin_password(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-005',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->tecnico)->put(route('electronicas.update', $electronica), [
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Intento sin clave',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['admin_password']);
    }

    public function test_electronica_anular_reactivar(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-006',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test anular',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        // Anular
        $response = $this->actingAs($this->admin)->post(route('electronicas.anular', $electronica), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $electronica->refresh();
        $this->assertEquals(1, $electronica->anulado);

        // Reactivar
        $response = $this->actingAs($this->admin)->post(route('electronicas.anular', $electronica), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $electronica->refresh();
        $this->assertEquals(0, $electronica->anulado);
    }

    public function test_electronica_anular_requires_admin_password_for_tecnico(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-007',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test anular tecnico',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('electronicas.anular', $electronica), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $electronica->refresh();
        $this->assertEquals(1, $electronica->anulado);
    }

    public function test_electronica_stock_association(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-008',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test stock',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('electronicas.stocks.store', $electronica), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('electronica_stock', [
            'electronica_id' => $electronica->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->cantidad);
    }

    public function test_electronica_stock_destroy_returns_stock(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-009',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test stock destroy',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        // Simulate proper stock attachment via service (decrements stock)
        app(\App\Services\StockService::class)->salida($this->stock, 2);
        $electronica->stocks()->attach($this->stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);

        $response = $this->actingAs($this->admin)->delete(route('electronicas.stocks.destroy', [$electronica->id, $this->stock->id]), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('electronica_stock', [
            'electronica_id' => $electronica->id,
            'stock_id' => $this->stock->id,
        ]);

        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_electronica_abono_store(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-010',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test abono',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('electronicas.abonos.store', $electronica), [
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono inicial',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'electronica_id' => $electronica->id,
            'monto' => 50000,
        ]);

        $electronica->refresh();
        $this->assertEquals(50000, $electronica->total_abonado);
        $this->assertEquals(100000, $electronica->saldo_pendiente);
    }

    public function test_electronica_abono_destroy(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-011',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test abono destroy',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $abono = \App\Models\Abono::create([
            'electronica_id' => $electronica->id,
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono inicial',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('electronicas.abonos.destroy', $abono));

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', ['id' => $abono->id, 'anulado' => true]);

        $electronica->refresh();
        $this->assertEquals(0, $electronica->total_abonado);
    }

    public function test_electronica_reportes_export_excel(): void
    {
        Electronica::create([
            'id_orden' => 'ELC-EXCEL-1',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test export',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('electronicas.reportes', ['export' => 'excel']));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_electronica_reportes_export_pdf(): void
    {
        Electronica::create([
            'id_orden' => 'ELC-PDF-1',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test export',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('electronicas.reportes', ['export' => 'pdf']));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_electronica_consulta_requires_both_cedula_and_orden(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-CONSULTA-1',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test consulta',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $invitado = User::factory()->create(['role' => 'invitado']);

        // Solo cédula
        $response = $this->actingAs($invitado)->get(route('consulta.electronicas', ['identificacion' => $this->cliente->identificacion]));
        $response->assertSessionHas('error');

        // Solo orden
        $response = $this->actingAs($invitado)->get(route('consulta.electronicas', ['id_orden' => 'ELC-CONSULTA-1']));
        $response->assertSessionHas('error');

        // Ambos correctos
        $response = $this->actingAs($invitado)->get(route('consulta.electronicas', [
            'identificacion' => $this->cliente->identificacion,
            'id_orden' => 'ELC-CONSULTA-1',
        ]));
        $response->assertStatus(200);
        $response->assertSee('ELC-CONSULTA-1');
    }

    public function test_electronica_consulta_rejects_wrong_cedula(): void
    {
        $electronica = Electronica::create([
            'id_orden' => 'ELC-CONSULTA-2',
            'equipo_id' => $this->equipo->id,
            'descripcion_problema' => 'Test consulta privacidad',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
        ]);

        $invitado = User::factory()->create(['role' => 'invitado']);

        $response = $this->actingAs($invitado)->get(route('consulta.electronicas', [
            'identificacion' => '999999999',
            'id_orden' => 'ELC-CONSULTA-2',
        ]));
        $response->assertStatus(200);
        $response->assertDontSee('Test consulta privacidad');
        $response->assertSee('No se encontró ninguna orden');
    }
}