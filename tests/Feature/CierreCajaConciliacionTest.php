<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreCajaConciliacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ConceptoCaja $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->concepto = ConceptoCaja::create([
            'nombre' => 'Venta Mostrador',
        ]);
    }

    /**
 * Sin conteo físico el cierre NO puede afirmar que cuadró: queda 'sin_conciliar'.
 * Regresión: antes el sistema precargaba el valor teórico y reportaba 'cuadrado'.
 */
public function test_cierre_sin_conteo_fisico_queda_sin_conciliar_y_no_afirma_cuadre(): void
    {
        $fecha = '2026-10-01';

        MovimientoCaja::create([
            'fecha' => $fecha,
            'persona' => 'Cliente A',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 350000,
            'estado' => 'activo',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'observaciones' => 'Cierre sin arqueo detallado',
        ]);

        $response->assertRedirect(route('cierre.index'));

        $cierre = CierreCaja::whereDate('fecha', $fecha)->first();
        $this->assertNotNull($cierre);

        // El efectivo teórico del sistema se conserva intacto.
        $this->assertEquals(350000, (float) $cierre->efectivo);

        // Pero no se inventa un conteo.
        $this->assertNull($cierre->efectivo_real_contado);
        $this->assertSame('sin_conciliar', $cierre->estado_diferencia);
        $this->assertSame('Sin conciliar', $cierre->estado_diferencia_label);
    }

    public function test_cierre_con_efectivo_exacto_queda_cuadrado(): void
    {
        $fecha = '2026-10-01';

        MovimientoCaja::create([
            'fecha' => $fecha,
            'persona' => 'Cliente B',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 500000,
            'estado' => 'activo',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'efectivo_real_contado' => 500000,
        ]);

        $response->assertRedirect(route('cierre.index'));

        $cierre = CierreCaja::whereDate('fecha', $fecha)->first();
        $this->assertEquals(500000, (float) $cierre->efectivo_real_contado);
        $this->assertEquals(0, (float) $cierre->diferencia);
        $this->assertEquals('cuadrado', $cierre->estado_diferencia);
    }

    public function test_cierre_con_faltante_calcula_diferencia_negativa_y_guarda_motivo(): void
    {
        $fecha = '2026-10-01';

        MovimientoCaja::create([
            'fecha' => $fecha,
            'persona' => 'Cliente C',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 480000,
            'estado' => 'activo',
            'user_id' => $this->admin->id,
        ]);

        // Cajero contó 460.000 físicamente (faltante de $20.000)
        $response = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'efectivo_real_contado' => 460000,
            'motivo_diferencia' => 'Faltante de 20.000 por vuelto mal entregado en horas de la mañana',
        ]);

        $response->assertRedirect(route('cierre.index'));

        $cierre = CierreCaja::whereDate('fecha', $fecha)->first();
        $this->assertEquals(480000, (float) $cierre->efectivo);
        $this->assertEquals(460000, (float) $cierre->efectivo_real_contado);
        $this->assertEquals(-20000, (float) $cierre->diferencia);
        $this->assertEquals('faltante', $cierre->estado_diferencia);
        $this->assertStringContainsString('vuelto mal entregado', $cierre->motivo_diferencia);
    }

    public function test_cierre_con_sobrante_calcula_diferencia_positiva(): void
    {
        $fecha = '2026-10-01';

        MovimientoCaja::create([
            'fecha' => $fecha,
            'persona' => 'Cliente D',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 300000,
            'estado' => 'activo',
            'user_id' => $this->admin->id,
        ]);

        // Cajero contó 315.000 (sobrante de $15.000)
        $response = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'efectivo_real_contado' => '315.000', // con formato miles
            'motivo_diferencia' => 'Sobrante propina no registrada',
        ]);

        $response->assertRedirect(route('cierre.index'));

        $cierre = CierreCaja::whereDate('fecha', $fecha)->first();
        $this->assertEquals(300000, (float) $cierre->efectivo);
        $this->assertEquals(315000, (float) $cierre->efectivo_real_contado);
        $this->assertEquals(15000, (float) $cierre->diferencia);
        $this->assertEquals('sobrante', $cierre->estado_diferencia);
    }

    public function test_actualizar_motivo_diferencia_en_edicion_de_cierre(): void
    {
        $cierre = CierreCaja::create([
            'fecha' => '2026-10-01',
            'total_ingresos' => 100000,
            'total_egresos' => 0,
            'efectivo' => 100000,
            'efectivo_real_contado' => 95000,
            'diferencia' => -5000,
            'consignacion' => 0,
            'saldo_final' => 100000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('cierre.update', $cierre->id), [
            'observaciones' => 'Revisión por gerencia',
            'motivo_diferencia' => 'Se aclaró faltante: descuento autorizado en caja',
        ]);

        $response->assertRedirect(route('cierre.show', $cierre->id));

        $cierre->refresh();
        $this->assertEquals('Revisión por gerencia', $cierre->observaciones);
        $this->assertEquals('Se aclaró faltante: descuento autorizado en caja', $cierre->motivo_diferencia);
    }

    public function test_vistas_cierre_y_caja_renderizan_correctamente_con_conciliacion_y_ancho_homogeneo(): void
    {
        $fecha = '2026-10-01';
        $mov = MovimientoCaja::create([
            'fecha' => $fecha,
            'persona' => 'Cliente Test',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 200000,
            'estado' => 'activo',
            'user_id' => $this->admin->id,
        ]);

        // 1. Probar renderizado de caja.edit (debe ser max-w-7xl homogéneo con caja.show)
        $respCajaEdit = $this->actingAs($this->admin)->get(route('caja.edit', $mov->id));
        $respCajaEdit->assertOk();
        $respCajaEdit->assertSee('max-w-7xl');
        $respCajaEdit->assertSee('Editar Movimiento');

        // 2. Probar renderizado de cierre.index (debe tener Arqueo y Conciliación Física)
        $respCierreIndex = $this->actingAs($this->admin)->get(route('cierre.index'));
        $respCierreIndex->assertOk();
        $respCierreIndex->assertSee('Conciliación Física');
        $respCierreIndex->assertSee('Dinero Contado en Mano');
        $respCierreIndex->assertSee('Abrir Desglose de Billetes');

        // 3. Crear cierre con arqueo y probar cierre.show y cierre.edit
        $cierre = CierreCaja::create([
            'fecha' => '2026-09-30',
            'total_ingresos' => 200000,
            'total_egresos' => 0,
            'efectivo' => 200000,
            'efectivo_real_contado' => 190000,
            'diferencia' => -10000,
            'consignacion' => 0,
            'saldo_final' => 200000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'motivo_diferencia' => 'Ajuste de caja menor',
            'user_id' => $this->admin->id,
        ]);

        $respCierreShow = $this->actingAs($this->admin)->get(route('cierre.show', $cierre->id));
        $respCierreShow->assertOk();
        $respCierreShow->assertSee('Arqueo y Conciliación Física');
        $respCierreShow->assertSee('Faltante en Caja');
        $respCierreShow->assertSee('Ajuste de caja menor');

        $respCierreEdit = $this->actingAs($this->admin)->get(route('cierre.edit', $cierre->id));
        $respCierreEdit->assertOk();
        $respCierreEdit->assertSee('max-w-7xl');
        $respCierreEdit->assertSee('Motivo / Justificación del Descuadre');
        $respCierreEdit->assertSee('Ajuste de caja menor');
    }
}
