<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbonoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected Mantenimiento $mantenimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnicoM = Tecnico::factory()->create();

        $this->mantenimiento = Mantenimiento::factory()->create([
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
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);

        $response = $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => 50000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono inicial mantenimiento',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'mantenimiento_id' => $this->mantenimiento->id,
            'monto' => 50000,
            'anulado' => false,
        ]);

        $abono = Abono::where('mantenimiento_id', $this->mantenimiento->id)->first();
        $this->assertDatabaseHas('movimiento_cajas', [
            'abono_id' => $abono->id,
            'tipo_movimiento' => 'ingreso',
            'monto' => 50000,
            'anulado' => false,
        ]);

        $this->mantenimiento->refresh();
        $this->assertEquals(50000, $this->mantenimiento->total_abonado);
        $this->assertEquals(50000, $this->mantenimiento->saldo_pendiente);
    }

    public function test_tecnico_can_store_abono(): void
    {
        ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);

        $response = $this->actingAs($this->tecnico)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => 30000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'consignacion',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('abonos', [
            'mantenimiento_id' => $this->mantenimiento->id,
            'monto' => 30000,
            'tipo_pago' => 'consignacion',
        ]);
    }

    public function test_abono_pago_completo_actualiza_saldo_a_cero(): void
    {
        ConceptoCaja::create(['nombre' => 'Pago Mantenimiento']);

        $response = $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => 100000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ]);

        $response->assertRedirect();
        $this->mantenimiento->refresh();
        $this->assertEquals(100000, $this->mantenimiento->total_abonado);
        $this->assertEquals(0, $this->mantenimiento->saldo_pendiente);
    }

    public function test_abono_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), []);
        $response->assertSessionHasErrors(['monto', 'fecha', 'tipo_pago']);
    }

    public function test_abono_store_rejects_negative_monto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => -5000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ]);
        $response->assertSessionHasErrors(['monto']);
    }

    public function test_admin_can_destroy_abono_preservando_trazabilidad(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);
        $abono = Abono::create([
            'mantenimiento_id' => $this->mantenimiento->id,
            'monto' => 50000,
            'fecha' => Carbon::today(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $mov = MovimientoCaja::create([
            'abono_id' => $abono->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test Cliente',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('abonos.destroy', $abono));

        $response->assertRedirect();
        // El abono NO se borra físicamente de la base de datos
        $this->assertDatabaseHas('abonos', [
            'id' => $abono->id,
            'anulado' => true,
        ]);
        // El movimiento de caja conserva abono_id intacto y se marca anulado
        $this->assertDatabaseHas('movimiento_cajas', [
            'id' => $mov->id,
            'abono_id' => $abono->id,
            'anulado' => true,
            'estado' => 'anulado',
        ]);

        $this->mantenimiento->refresh();
        $this->assertEquals(0, $this->mantenimiento->total_abonado);
        $this->assertEquals(100000, $this->mantenimiento->saldo_pendiente);
    }

    public function test_tecnico_requires_admin_password_to_destroy_abono(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);
        $abono = Abono::create([
            'mantenimiento_id' => $this->mantenimiento->id,
            'monto' => 50000,
            'fecha' => Carbon::today(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);
        MovimientoCaja::create([
            'abono_id' => $abono->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test Cliente',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Sin contraseña de admin debe fallar
        $responseFail = $this->actingAs($this->tecnico)->delete(route('abonos.destroy', $abono), []);
        $responseFail->assertSessionHas('error');
        $this->assertDatabaseHas('abonos', ['id' => $abono->id, 'anulado' => false]);

        // Con contraseña de admin autoriza la anulación lógica
        $responseOk = $this->actingAs($this->tecnico)->delete(route('abonos.destroy', $abono), [
            'password_confirm' => 'password',
        ]);
        $responseOk->assertRedirect();
        $this->assertDatabaseHas('abonos', ['id' => $abono->id, 'anulado' => true]);
        $this->assertDatabaseHas('movimiento_cajas', ['abono_id' => $abono->id, 'anulado' => true]);
    }

    public function test_cannot_destroy_already_anulado_abono(): void
    {
        $abono = Abono::create([
            'mantenimiento_id' => $this->mantenimiento->id,
            'monto' => 50000,
            'fecha' => Carbon::today(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('abonos.destroy', $abono));
        $response->assertSessionHas('error');
    }
}
