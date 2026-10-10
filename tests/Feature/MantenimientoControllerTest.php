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

class MantenimientoControllerTest extends TestCase
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

    public function test_admin_can_view_mantenimiento_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('mantenimientos.index'));
        $response->assertStatus(200);
        $response->assertSee('Mantenimientos');
    }

    public function test_tecnico_can_view_mantenimiento_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('mantenimientos.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_mantenimiento_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('mantenimientos.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_mantenimiento(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'No enciende',
            'costo' => 150000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('mantenimientos.index'));
        $this->assertDatabaseHas('mantenimientos', [
            'equipo_id' => $this->equipo->id,
            'descripcion' => 'No enciende',
            'tipo' => 'correctivo',
        ]);
    }

    public function test_tecnico_can_create_mantenimiento(): void
    {
        $response = $this->actingAs($this->tecnico)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'preventivo',
            'reparacion' => 'software',
            'descripcion' => 'Mantenimiento preventivo',
            'costo' => 80000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('mantenimientos.index'));
    }

    public function test_guest_cannot_create_mantenimiento(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_mantenimiento_creation_requires_valid_equipo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'pendiente',
            'equipo_id' => 99999,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['equipo_id']);
    }

    public function test_mantenimiento_creation_requires_valid_tipo(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'invalido',
            'reparacion' => 'hardware',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['tipo']);
    }

    public function test_mantenimiento_creation_requires_valid_reparacion(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'invalido',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['reparacion']);
    }

    public function test_mantenimiento_creation_requires_valid_estado(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'invalido',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['estado']);
    }

    public function test_mantenimiento_creation_requires_valid_tecnico(): void
    {
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Test',
            'costo' => 100000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => 99999,
        ]);

        $response->assertSessionHasErrors(['tecnico_id']);
    }

    public function test_admin_can_view_mantenimiento_show(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-001',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test mantenimiento',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('mantenimientos.show', $mantenimiento));
        $response->assertStatus(200);
        $response->assertSee('ORD-001');
    }

    public function test_admin_can_edit_mantenimiento(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-002',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('mantenimientos.edit', $mantenimiento));
        $response->assertStatus(200);
        $response->assertSee('ORD-002');
    }

    public function test_admin_can_update_mantenimiento(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-003',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->put(route('mantenimientos.update', $mantenimiento), [
            'fecha_entrada' => now()->toDateString(),
            'fecha_salida' => now()->toDateString(),
            'tipo' => 'preventivo',
            'reparacion' => 'software',
            'descripcion' => 'Actualizado',
            'costo' => 100000,
            'estado' => 'terminado',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertRedirect(route('mantenimientos.index'));
        $mantenimiento->refresh();
        $this->assertEquals('Actualizado', $mantenimiento->descripcion);
        $this->assertEquals('preventivo', $mantenimiento->tipo);
        $this->assertEquals('software', $mantenimiento->reparacion);
        $this->assertEquals('terminado', $mantenimiento->estado);
    }

    public function test_tecnico_can_update_mantenimiento_with_admin_password(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-004',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->tecnico)->put(route('mantenimientos.update', $mantenimiento), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Actualizado por tecnico',
            'costo' => 150000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'admin_password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect(route('mantenimientos.index'));
        $mantenimiento->refresh();
        $this->assertEquals('Actualizado por tecnico', $mantenimiento->descripcion);
    }

    public function test_tecnico_cannot_update_mantenimiento_without_admin_password(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-005',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->tecnico)->put(route('mantenimientos.update', $mantenimiento), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'descripcion' => 'Intento sin clave',
            'costo' => 150000,
            'estado' => 'pendiente',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
        ]);

        $response->assertSessionHasErrors(['admin_password']);
    }

    public function test_mantenimiento_duplicate(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-DUP-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Original para duplicar',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.duplicate', $mantenimiento));

        $response->assertRedirect(route('mantenimientos.edit', $mantenimiento->fresh()->id + 1));
        $nuevo = Mantenimiento::where('id_orden', '!=', 'ORD-DUP-1')->latest('id')->first();
        $this->assertNotNull($nuevo);
        $this->assertEquals('Original para duplicar', $nuevo->descripcion);
        $this->assertEquals('pendiente', $nuevo->estado);
    }

    public function test_mantenimiento_anular_reactivar(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-ANUL-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test anular',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        // Anular
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.anular', $mantenimiento), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $mantenimiento->refresh();
        $this->assertTrue($mantenimiento->anulado);

        // Reactivar
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.anular', $mantenimiento), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $mantenimiento->refresh();
        $this->assertFalse($mantenimiento->anulado);
    }

    public function test_mantenimiento_anular_requires_admin_password_for_tecnico(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-ANUL-2',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test anular tecnico',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('mantenimientos.anular', $mantenimiento), [
            'admin_password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertRedirect();
        $mantenimiento->refresh();
        $this->assertTrue($mantenimiento->anulado);
    }

    public function test_mantenimiento_stock_association(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-STK-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test stock',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $mantenimiento), [
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('mantenimiento_stock', [
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $this->stock->id,
            'cantidad' => 1,
        ]);

        $this->stock->refresh();
        $this->assertEquals(9, $this->stock->cantidad);
    }

    public function test_mantenimiento_stock_destroy_returns_stock(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-STK-2',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test stock destroy',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $mantenimiento->stocks()->attach($this->stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);

        $response = $this->actingAs($this->admin)->delete(route('mantenimientos.stocks.destroy', [$mantenimiento->id, $this->stock->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('mantenimiento_stock', [
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $this->stock->id,
        ]);

        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_tecnico_cannot_remove_stock_without_admin_password(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-STK-3',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test tecnico no remove',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $mantenimiento->stocks()->attach($this->stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);

        $response = $this->actingAs($this->tecnico)->delete(route('mantenimientos.stocks.destroy', [$mantenimiento->id, $this->stock->id]));

        $response->assertRedirect();
        $this->assertDatabaseHas('mantenimiento_stock', [
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $this->stock->id,
        ]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);
    }

    public function test_admin_can_remove_stock_without_password(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-STK-4',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test admin remove',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $mantenimiento->stocks()->attach($this->stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        $this->stock->refresh();
        $this->assertEquals(8, $this->stock->cantidad);

        $response = $this->actingAs($this->admin)->delete(route('mantenimientos.stocks.destroy', [$mantenimiento->id, $this->stock->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('mantenimiento_stock', [
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $this->stock->id,
        ]);
        $this->stock->refresh();
        $this->assertEquals(10, $this->stock->cantidad);
    }

    public function test_mantenimiento_abono_store(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-ABN-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test abono',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('abonos.store', $mantenimiento), [
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono inicial',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
        ]);

        $mantenimiento->refresh();
        $this->assertEquals(50000, $mantenimiento->total_abonado);
        $this->assertEquals(100000, $mantenimiento->saldo_pendiente);
    }

    public function test_mantenimiento_abono_rejects_on_anulado(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-ABN-2',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test abono anulado',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'entregado',
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('abonos.store', $mantenimiento), [
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono a anulado',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('abonos', [
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
        ]);
    }

    public function test_mantenimiento_reportes_export_excel(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-EXCEL-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Export test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('mantenimientos.reportes', ['export' => 'excel']));
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_mantenimiento_reportes_export_pdf(): void
    {
        Mantenimiento::create([
            'id_orden' => 'ORD-PDF-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Export test',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('mantenimientos.reportes', ['export' => 'pdf']));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_mantenimiento_consulta_requires_both_cedula_and_orden(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-CONS-1',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test consulta',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        // Solo cédula
        $response = $this->get(route('consulta.mantenimientos', ['identificacion' => $this->cliente->identificacion]));
        $response->assertSessionHas('error');

        // Solo orden
        $response = $this->get(route('consulta.mantenimientos', ['id_orden' => 'ORD-CONS-1']));
        $response->assertSessionHas('error');

        // Ambos correctos
        $response = $this->get(route('consulta.mantenimientos', [
            'identificacion' => $this->cliente->identificacion,
            'id_orden' => 'ORD-CONS-1',
        ]));
        $response->assertStatus(200);
        $response->assertSee('ORD-CONS-1');
    }

    public function test_mantenimiento_consulta_rejects_wrong_cedula(): void
    {
        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-CONS-2',
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test consulta privacidad',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $response = $this->get(route('consulta.mantenimientos', [
            'identificacion' => '999999999',
            'id_orden' => 'ORD-CONS-2',
        ]));
        $response->assertStatus(200);
        $response->assertDontSee('Test consulta privacidad');
        $response->assertSee('No se encontró ningun resultado');
    }
}