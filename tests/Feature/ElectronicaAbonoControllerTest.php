<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Tecnico;
use App\Models\User;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicaAbonoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected Electronica $electronica;

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
            'costo' => 100000,
        ]);
    }

    public function test_admin_can_store_abono(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Electrónica']);

        $response = $this->actingAs($this->admin)->post(route('electronicas.abonos.store', $this->electronica), [
            'monto' => 50000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono de prueba',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'electronica_id' => $this->electronica->id,
            'monto' => 50000,
        ]);
        $this->assertDatabaseHas('movimiento_cajas', [
            'abono_id' => Abono::first()->id,
            'tipo_movimiento' => 'ingreso',
        ]);
    }

    public function test_tecnico_can_store_abono(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Electrónica']);

        $response = $this->actingAs($this->tecnico)->post(route('electronicas.abonos.store', $this->electronica), [
            'monto' => 30000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono técnico',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'electronica_id' => $this->electronica->id,
            'monto' => 30000,
        ]);
    }

    public function test_abono_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.abonos.store', $this->electronica), [
            'monto' => '',
            'fecha' => '',
            'tipo_pago' => '',
        ]);

        $response->assertSessionHasErrors(['monto', 'fecha', 'tipo_pago']);
    }

    public function test_abono_store_rejects_negative_monto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('electronicas.abonos.store', $this->electronica), [
            'monto' => -100,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors('monto');
    }

    public function test_admin_can_destroy_abono(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Electrónica']);
        $abono = Abono::create([
            'electronica_id' => $this->electronica->id,
            'monto' => 50000,
            'fecha' => Carbon::today(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
        ]);
        MovimientoCaja::create([
            'abono_id' => $abono->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('electronicas.abonos.destroy', $abono), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('abonos', ['id' => $abono->id]);
    }

    public function test_tecnico_requires_admin_password_to_destroy_abono(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Electrónica']);
        $abono = Abono::create([
            'electronica_id' => $this->electronica->id,
            'monto' => 50000,
            'fecha' => Carbon::today(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
        ]);
        MovimientoCaja::create([
            'abono_id' => $abono->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->tecnico)->delete(route('electronicas.abonos.destroy', $abono), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('abonos', ['id' => $abono->id]);
    }

    public function test_destroy_abono_requires_admin_password(): void
    {
        // Create abono via controller
        $response = $this->actingAs($this->admin)->post(route('electronicas.abonos.store', $this->electronica), [
            'monto' => 50000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Test abono',
        ]);
        $response->assertRedirect();
        $abono = Abono::latest()->first();

        // Admin doesn't need password, test with tecnico
        $response = $this->actingAs($this->tecnico)->delete(route('electronicas.abonos.destroy', $abono), []);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('contraseña', $response->getSession()->get('error'));
    }
}