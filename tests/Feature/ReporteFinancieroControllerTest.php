<?php

namespace Tests\Feature;

use App\Models\ConceptoCaja;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteFinancieroControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $tecnico;
    protected User $invitado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);
        $this->invitado = User::factory()->create(['role' => 'invitado']);
    }

    public function test_admin_can_view_reporte_diario(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Servicios']);
        $hoy = now()->toDateString();

        // Ingreso activo
        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'monto' => 150000,
            'fecha' => $hoy,
            'tipo_pago' => 'efectivo',
            'concepto_id' => $concepto->id,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Egreso activo
        MovimientoCaja::create([
            'tipo_movimiento' => 'egreso',
            'monto' => 30000,
            'fecha' => $hoy,
            'tipo_pago' => 'efectivo',
            'concepto_id' => $concepto->id,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Movimiento anulado (NO debe sumar al total real)
        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'monto' => 500000,
            'fecha' => $hoy,
            'tipo_pago' => 'efectivo',
            'concepto_id' => $concepto->id,
            'estado' => 'anulado',
            'anulado' => true,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.financiero.diario', ['fecha' => $hoy]));

        $response->assertStatus(200);
        $response->assertViewHas('resumen');
        $resumen = $response->viewData('resumen');

        $this->assertEquals(150000, $resumen['total_ingresos']);
        $this->assertEquals(30000, $resumen['total_egresos']);
        $this->assertEquals(120000, $resumen['saldo_caja']);
    }

    public function test_admin_can_view_reporte_acumulado(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.financiero.acumulado', ['rango' => 'mes']));
        $response->assertStatus(200);
        $response->assertViewHas('acumulado');
    }

    public function test_admin_can_view_reporte_operaciones(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reportes.financiero.operaciones', [
            'tipo' => 'solo_mantenimientos',
        ]));
        $response->assertStatus(200);
        $response->assertViewHas('registros');
    }

    public function test_invitado_cannot_access_reportes_financieros(): void
    {
        $resDiario = $this->actingAs($this->invitado)->get(route('reportes.financiero.diario'));
        $this->assertContains($resDiario->status(), [302, 403]);

        $resAcumulado = $this->actingAs($this->invitado)->get(route('reportes.financiero.acumulado'));
        $this->assertContains($resAcumulado->status(), [302, 403]);

        $resOperaciones = $this->actingAs($this->invitado)->get(route('reportes.financiero.operaciones'));
        $this->assertContains($resOperaciones->status(), [302, 403]);
    }
}
