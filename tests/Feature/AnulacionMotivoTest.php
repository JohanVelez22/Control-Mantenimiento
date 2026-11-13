<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\Evento;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M-09: anular un documento con dinero exige motivo y el motivo queda en el historial.
 */
class AnulacionMotivoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    public function test_anular_orden_sin_motivo_se_rechaza_y_con_motivo_queda_registrado(): void
    {
        $orden = Mantenimiento::factory()->create(['anulado' => false, 'estado' => 'pendiente']);

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $orden), ['accion' => 'anular'])
            ->assertSessionHas('error');
        $this->assertFalse((bool) $orden->fresh()->anulado);

        $this->post(route('mantenimientos.anular', $orden), ['accion' => 'anular', 'motivo' => 'Cliente desistió de la reparación']);
        $this->assertTrue((bool) $orden->fresh()->anulado);

        $evento = Evento::where('accion', 'motivo_anulacion')->where('modelo_id', $orden->id)->firstOrFail();
        $this->assertStringContainsString('Cliente desistió', $evento->descripcion);
    }

    public function test_reactivar_no_pide_motivo(): void
    {
        $orden = Mantenimiento::factory()->create(['anulado' => true, 'estado' => 'pendiente']);

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $orden), ['accion' => 'reactivar']);

        $this->assertFalse((bool) $orden->fresh()->anulado);
    }

    public function test_anular_movimiento_de_caja_exige_motivo(): void
    {
        $mov = MovimientoCaja::create([
            'persona' => 'Ana', 'fecha' => now()->toDateString(), 'concepto_id' => ConceptoCaja::create(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 5000,
            'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('caja.anular', $mov), ['accion' => 'anular', 'motivo' => 'no'])
            ->assertSessionHas('error');
        $this->assertFalse((bool) $mov->fresh()->anulado);

        $this->post(route('caja.anular', $mov), ['accion' => 'anular', 'motivo' => 'Ingreso registrado dos veces']);
        $this->assertTrue((bool) $mov->fresh()->anulado);
        $this->assertTrue(Evento::where('accion', 'motivo_anulacion')->where('modelo_id', $mov->id)->exists());
    }

    public function test_anular_abono_exige_motivo(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 50000, 'anulado' => false, 'estado' => 'pendiente']);
        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => 20000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ]);
        $abono = Abono::firstOrFail();

        $this->delete(route('abonos.destroy', $abono))->assertSessionHas('error');
        $this->assertFalse((bool) $abono->fresh()->anulado);

        $this->delete(route('abonos.destroy', $abono), ['motivo' => 'Se devolvió el dinero al cliente']);
        $this->assertTrue((bool) $abono->fresh()->anulado);
        $this->assertEquals(0, $orden->fresh()->total_abonado);
    }
}
