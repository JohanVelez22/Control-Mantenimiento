<?php

namespace Tests\Feature;

use App\Models\ConceptoCaja;
use App\Models\CierreCaja;
use App\Models\MovimientoCaja;
use App\Services\CierreCajaGuard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimientoCajaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected ConceptoCaja $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->concepto = ConceptoCaja::create(['nombre' => 'Servicio General']);
    }

    public function test_admin_can_view_caja_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('caja.index'));
        $response->assertStatus(200);
        $response->assertSee('Movimientos de Caja');
    }

    public function test_tecnico_can_view_caja_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('caja.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_create_ingreso_movimiento(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'descripcion' => 'Ingreso de prueba',
        ]);

        $response->assertRedirect(route('caja.index'));
        $this->assertDatabaseHas('movimiento_cajas', [
            'persona' => 'Cliente Test',
            'tipo_movimiento' => 'ingreso',
            'monto' => 100000,
        ]);
    }

    public function test_admin_can_create_egreso_movimiento(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Proveedor Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'egreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'Egreso de prueba',
        ]);

        $response->assertRedirect(route('caja.index'));
        $this->assertDatabaseHas('movimiento_cajas', [
            'persona' => 'Proveedor Test',
            'tipo_movimiento' => 'egreso',
            'monto' => 50000,
        ]);
    }

    public function test_movimiento_creation_requires_valid_tipo_movimiento(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'invalido',
            'tipo_pago' => 'efectivo',
            'monto' => 10000,
            'descripcion' => 'Test',
        ]);

        $response->assertSessionHasErrors(['tipo_movimiento']);
    }

    public function test_movimiento_creation_requires_valid_tipo_pago(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'invalido',
            'monto' => 10000,
            'descripcion' => 'Test',
        ]);

        $response->assertSessionHasErrors(['tipo_pago']);
    }

    public function test_movimiento_creation_requires_valid_concepto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => 99999,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 10000,
            'descripcion' => 'Test',
        ]);

        $response->assertSessionHasErrors(['concepto_id']);
    }

    public function test_movimiento_with_monto_total_and_saldo_pendiente(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Cliente Saldo',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 30000,
            'monto_total' => 100000,
            'descripcion' => 'Test saldo pendiente',
        ]);

        $response->assertRedirect(route('caja.index'));
        $mov = MovimientoCaja::where('descripcion', 'Test saldo pendiente')->first();
        $this->assertNotNull($mov);
        $this->assertEquals(30000, $mov->monto);
        $this->assertEquals(100000, $mov->monto_total);
        $this->assertEquals(70000, $mov->saldo_pendiente);
    }

    public function test_admin_can_view_movimiento_edit(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'descripcion' => 'Para editar',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('caja.edit', $movimiento));
        $response->assertStatus(200);
        $response->assertSee('Editar Movimiento');
    }

    public function test_admin_can_update_movimiento(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'descripcion' => 'Para editar',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('caja.update', $movimiento), [
            'persona' => 'Cliente Actualizado',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 150000,
            'descripcion' => 'Actualizado',
        ]);

        $response->assertRedirect(route('caja.index'));
        $movimiento->refresh();
        $this->assertEquals('Cliente Actualizado', $movimiento->persona);
        $this->assertEquals(150000, $movimiento->monto);
        $this->assertEquals('Actualizado', $movimiento->descripcion);
    }

    public function test_admin_can_anular_movimiento(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Anular',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'descripcion' => 'Para anular',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.anular', $movimiento), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $movimiento->refresh();
        $this->assertTrue($movimiento->anulado);
        $this->assertEquals('anulado', $movimiento->estado);
    }

    public function test_tecnico_can_anular_movimiento_with_admin_password(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Tec Anular',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'descripcion' => 'Para anular tecnico',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('caja.anular', $movimiento), [
            'admin_password' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $movimiento->refresh();
        $this->assertTrue($movimiento->anulado);
    }

    public function test_anular_movimiento_with_child_payments_cascades(): void
    {
        $padre = MovimientoCaja::create([
            'persona' => 'Cliente Padre',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'monto_total' => 100000,
            'descripcion' => 'Padre con abonos',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $hijo = MovimientoCaja::create([
            'persona' => 'Cliente Padre',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 20000,
            'descripcion' => 'Abono hijo',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'parent_id' => $padre->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.anular', $padre), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $padre->refresh();
        $hijo->refresh();
        $this->assertTrue($padre->anulado);
        $this->assertTrue($hijo->anulado);
    }

    public function test_abono_store_reduces_saldo_pendiente(): void
    {
        $padre = MovimientoCaja::create([
            'persona' => 'Cliente Abono',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 20000,
            'monto_total' => 100000,
            'descripcion' => 'Padre para abono',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals(80000, $padre->saldo_pendiente);

        $response = $this->actingAs($this->admin)->post(route('caja.abonos.store', $padre), [
            'monto_abono' => 30000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono parcial',
        ]);

        $response->assertRedirect(route('caja.index'));
        $padre->refresh();
        $this->assertEquals(50000, $padre->saldo_pendiente);

        $abono = MovimientoCaja::where('parent_id', $padre->id)->first();
        $this->assertNotNull($abono);
        $this->assertEquals(30000, $abono->monto);
    }

    public function test_abono_store_rejects_monto_superior_a_saldo(): void
    {
        $padre = MovimientoCaja::create([
            'persona' => 'Cliente Abono Error',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 20000,
            'monto_total' => 100000,
            'descripcion' => 'Padre error abono',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.abonos.store', $padre), [
            'monto_abono' => 90000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors(['monto_abono']);
        $this->assertStringContainsString('supera el saldo', session('errors')->first('monto_abono'));
    }

    public function test_abono_destroy_reverts_saldo_pendiente(): void
    {
        $padre = MovimientoCaja::create([
            'persona' => 'Cliente Abono Destroy',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 20000,
            'monto_total' => 100000,
            'descripcion' => 'Padre destroy',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $abono = MovimientoCaja::create([
            'persona' => 'Cliente Abono Destroy',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 30000,
            'descripcion' => 'Abono para destruir',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'parent_id' => $padre->id,
        ]);

        $padre->refresh();
        $this->assertEquals(50000, $padre->saldo_pendiente);

        $response = $this->actingAs($this->admin)->delete(route('caja.abonos.destroy', $abono));

        $response->assertRedirect(route('caja.index'));
        $padre->refresh();
        $this->assertEquals(80000, $padre->saldo_pendiente);
        $this->assertNull(MovimientoCaja::find($abono->id));
    }

    public function test_concepto_store_creates_new_concepto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.concepto.store'), [
            'nombre' => 'Nuevo Concepto Test',
        ]);

        $response->assertRedirect(route('caja.index'));
        $this->assertDatabaseHas('concepto_cajas', ['nombre' => 'Nuevo Concepto Test']);
    }

    public function test_concepto_store_validates_required(): void
    {
        $response = $this->actingAs($this->admin)->post(route('caja.concepto.store'), [
            'nombre' => '',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_concepto_store_rejects_duplicate(): void
    {
        ConceptoCaja::create(['nombre' => 'Duplicado']);

        $response = $this->actingAs($this->admin)->post(route('caja.concepto.store'), [
            'nombre' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors(['nombre']);
    }

    public function test_caja_duplicate_creates_copy(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Duplicar',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'Original',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.duplicate', $movimiento));

        $response->assertRedirect(route('caja.edit', $movimiento->fresh()->id + 1));
        $copia = MovimientoCaja::where('descripcion', 'Copia de: Original')->first();
        $this->assertNotNull($copia);
        $this->assertEquals(50000, $copia->monto);
        $this->assertEquals('activo', $copia->estado);
        $this->assertFalse($copia->anulado);
    }

    public function test_caja_print_view(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Print',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'Para imprimir',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('caja.print', $movimiento));
        $response->assertStatus(200);
        $response->assertSee('Para imprimir');
    }

    public function test_anular_movimiento_respects_cierre_caja(): void
    {
        $fechaCerrada = now()->subDays(2)->toDateString();
        CierreCaja::create([
            'fecha' => $fechaCerrada,
            'total_ingresos' => 100000,
            'total_egresos' => 0,
            'efectivo' => 100000,
            'consignacion' => 0,
            'saldo_final' => 100000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Cerrado',
            'fecha' => $fechaCerrada,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'En día cerrado',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.anular', $movimiento), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $movimiento->refresh();
        $this->assertTrue($movimiento->anulado);
    }
}