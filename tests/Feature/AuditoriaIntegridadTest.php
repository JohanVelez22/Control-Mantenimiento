<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresiones de la auditoría de noviembre 2026 (integridad financiera y de estados).
 * Cada test reproduce un escenario que antes producía dinero fantasma o un estado incoherente.
 */
class AuditoriaIntegridadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Mantenimiento $mantenimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);

        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);

        $this->mantenimiento = Mantenimiento::factory()->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => Tecnico::factory()->create()->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
            'estado' => 'pendiente',
            'costo' => 100000,
        ]);
    }

    private function abonar(int $monto): void
    {
        $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => $monto,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ])->assertRedirect();
    }

    private function ingresosActivos(): float
    {
        return (float) MovimientoCaja::where('tipo_movimiento', 'ingreso')
            ->where('anulado', false)->where('estado', 'activo')->sum('monto');
    }

    /** Pago total sin concepto "Abono Mantenimiento" sembrado: anular la orden debe anular su ingreso en caja. */
    public function test_anular_orden_con_pago_total_anula_el_ingreso_en_caja(): void
    {
        $this->abonar(100000);
        $this->assertEquals(100000, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento))->assertRedirect();

        $this->assertTrue((bool) $this->mantenimiento->fresh()->anulado);
        $this->assertEquals(0, $this->ingresosActivos());
    }

    /** Un abono eliminado antes de anular la orden no debe revivir al reactivarla. */
    public function test_reactivar_orden_no_revive_abonos_eliminados_individualmente(): void
    {
        $this->abonar(30000);
        $abonoEliminado = Abono::first();
        $this->actingAs($this->admin)->delete(route('abonos.destroy', $abonoEliminado))->assertRedirect();

        $this->abonar(50000);
        $this->assertEquals(50000, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento));
        $this->assertEquals(0, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento));
        $this->assertFalse((bool) $this->mantenimiento->fresh()->anulado);

        $this->assertTrue($abonoEliminado->fresh()->anulado, 'El abono eliminado no debe reactivarse');
        $this->assertEquals(50000, $this->ingresosActivos());
        $this->assertEquals(50000, $this->mantenimiento->fresh()->total_abonado);
    }

    /** Doble envío del formulario de anulación: la segunda petición no debe reactivar la orden. */
    public function test_doble_envio_de_anulacion_no_invierte_la_operacion(): void
    {
        $this->abonar(40000);

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento), ['accion' => 'anular']);
        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento), ['accion' => 'anular'])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $this->mantenimiento->fresh()->anulado);
        $this->assertEquals(0, $this->ingresosActivos());
    }
}
