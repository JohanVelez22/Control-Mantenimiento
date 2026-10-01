<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreCajaBloqueoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ConceptoCaja $concepto;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'administrador@tecnisystemas.com',
            'role' => 'admin',
            'active' => true,
        ]);

        $this->concepto = ConceptoCaja::create(['nombre' => 'Servicio General']);
        $this->cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'identificacion' => '12345678',
            'email' => 'juan@test.com',
            'movil' => '3001234567',
        ]);
    }

    private function cerrarDia(string $fecha): CierreCaja
    {
        return CierreCaja::create([
            'fecha' => $fecha,
            'total_ingresos' => 100000,
            'total_egresos' => 0,
            'efectivo' => 100000,
            'consignacion' => 0,
            'saldo_final' => 100000,
            'num_movimientos' => 1,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_no_se_puede_crear_movimiento_de_caja_en_dia_cerrado(): void
    {
        $fechaCerrada = '2026-09-25';
        $this->cerrarDia($fechaCerrada);

        $response = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Juan Pérez',
            'fecha' => $fechaCerrada,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 500000,
            'descripcion' => 'Intento de ingreso en día cerrado',
        ]);

        $response->assertSessionHasErrors(['fecha']);
        $this->assertEquals(0, MovimientoCaja::whereDate('fecha', $fechaCerrada)->count());
    }

    public function test_no_se_puede_editar_movimiento_perteneciente_a_dia_cerrado(): void
    {
        $fecha = '2026-09-26';
        $movimiento = MovimientoCaja::create([
            'persona' => 'Juan Pérez',
            'fecha' => $fecha,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Se cierra el día
        $this->cerrarDia($fecha);

        // Intento de abrir vista de edición es redirigido
        $responseGet = $this->actingAs($this->admin)->get(route('caja.edit', $movimiento));
        $responseGet->assertRedirect(route('caja.index'));
        $responseGet->assertSessionHas('error');

        // Intento de actualizar el movimiento falla
        $responsePut = $this->actingAs($this->admin)->put(route('caja.update', $movimiento), [
            'persona' => 'Juan Pérez Modificado',
            'fecha' => $fecha,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 600000, // alteración de saldo
        ]);

        $responsePut->assertSessionHasErrors(['fecha']);
        $movimiento->refresh();
        $this->assertEquals(100000, (float) $movimiento->monto);
    }

    public function test_no_se_puede_anular_movimiento_de_caja_en_dia_cerrado(): void
    {
        $fecha = '2026-09-27';
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => $fecha,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 500000,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Se cierra el día
        $this->cerrarDia($fecha);

        // Intento de anular el movimiento es bloqueado
        $response = $this->actingAs($this->admin)->post(route('caja.anular', $movimiento));
        $response->assertSessionHas('error');

        $movimiento->refresh();
        $this->assertFalse($movimiento->anulado);
        $this->assertEquals('activo', $movimiento->estado);
    }

    public function test_no_se_puede_registrar_abono_de_caja_en_dia_cerrado(): void
    {
        $movimientoPadre = MovimientoCaja::create([
            'persona' => 'Cliente Crédito',
            'fecha' => '2026-09-20',
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 200000,
            'monto_total' => 500000,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $fechaCerrada = '2026-09-28';
        $this->cerrarDia($fechaCerrada);

        $response = $this->actingAs($this->admin)->post(route('caja.abonos.store', $movimientoPadre), [
            'monto_abono' => 150000,
            'fecha' => $fechaCerrada,
            'tipo_pago' => 'efectivo',
        ]);

        $response->assertSessionHasErrors(['fecha']);
        $this->assertEquals(0, MovimientoCaja::where('parent_id', $movimientoPadre->id)->count());
    }

    public function test_no_se_puede_eliminar_abono_de_mantenimiento_si_su_fecha_esta_cerrada(): void
    {
        $equipo = \App\Models\Equipo::create([
            'nombre' => 'Laptop HP',
            'marca' => 'HP',
            'modelo' => 'Pavilion',
            'serie' => 'SN1234',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $tecnico = Tecnico::create([
            'nombre' => 'Pedro Técnico',
            'identificacion' => '88889999',
            'especialidad' => 'General',
            'movil' => '3009998877',
            'email' => 'pedro@test.com',
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-TEST-99',
            'equipo_id' => $equipo->id,
            'user_id' => $this->admin->id,
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $tecnico->id,
            'equipo' => 'Laptop HP',
            'marca' => 'HP',
            'modelo' => 'Pavilion',
            'serie' => 'SN1234',
            'descripcion' => 'Revisión técnica preventiva',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 300000,
            'abono' => 100000,
            'estado' => 'pendiente',
            'fecha_entrada' => '2026-09-21',
        ]);

        $fechaAbono = '2026-09-22';
        $abono = Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'user_id' => $this->admin->id,
            'monto' => 100000,
            'fecha' => $fechaAbono,
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono inicial',
        ]);

        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'fecha' => $fechaAbono,
            'monto' => 100000,
            'concepto_id' => $this->concepto->id,
            'persona' => 'Juan Pérez',
            'descripcion' => 'Abono Parcial Orden ORD-TEST-99',
            'tipo_pago' => 'efectivo',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'abono_id' => $abono->id,
        ]);

        // Se cierra la fecha del abono
        $this->cerrarDia($fechaAbono);

        // Intento de eliminar el abono debe ser rechazado
        $response = $this->actingAs($this->admin)->delete(route('abonos.destroy', $abono));
        $response->assertSessionHas('error');

        $this->assertNotNull(Abono::find($abono->id));
        $movCaja = MovimientoCaja::where('abono_id', $abono->id)->first();
        $this->assertFalse($movCaja->anulado);
    }

    public function test_eliminar_cierre_de_caja_reabre_el_dia_para_operaciones_legitimas(): void
    {
        $fecha = '2026-09-29';
        $cierre = $this->cerrarDia($fecha);

        // En estado cerrado, crear movimiento falla
        $respFail = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Cliente Autorizado',
            'fecha' => $fecha,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 80000,
        ]);
        $respFail->assertSessionHasErrors(['fecha']);

        // El administrador autorizado elimina el cierre para reabrir el día
        $respDelete = $this->actingAs($this->admin)->delete(route('cierre.destroy', $cierre));
        $respDelete->assertRedirect(route('cierre.index'));
        $this->assertNull(CierreCaja::find($cierre->id));

        // Ahora el registro en esa fecha tiene éxito
        $respOk = $this->actingAs($this->admin)->post(route('caja.store'), [
            'persona' => 'Cliente Autorizado',
            'fecha' => $fecha,
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 80000,
        ]);
        $respOk->assertRedirect(route('caja.index'));
        $this->assertEquals(1, MovimientoCaja::whereDate('fecha', $fecha)->count());
    }

    public function test_no_se_puede_anular_mantenimiento_si_tiene_abonos_en_dia_cerrado(): void
    {
        $equipo = \App\Models\Equipo::create([
            'nombre' => 'PC Dell',
            'marca' => 'Dell',
            'modelo' => 'Optiplex',
            'serie' => 'SN-DEL-1',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $tecnico = Tecnico::create([
            'nombre' => 'Tecnico 2',
            'identificacion' => '112233',
            'especialidad' => 'PC',
            'movil' => '3110001122',
            'email' => 'tec2@test.com',
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-CERRADA-1',
            'equipo_id' => $equipo->id,
            'user_id' => $this->admin->id,
            'cliente_id' => $this->cliente->id,
            'tecnico_id' => $tecnico->id,
            'equipo' => 'PC Dell',
            'marca' => 'Dell',
            'modelo' => 'Optiplex',
            'serie' => 'SN-DEL-1',
            'descripcion' => 'Mantenimiento preventivo',
            'tipo' => 'preventivo',
            'reparacion' => 'hardware',
            'costo' => 200000,
            'abono' => 50000,
            'estado' => 'pendiente',
            'fecha_entrada' => '2026-09-10',
        ]);

        $fechaAbono = '2026-09-12';
        Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'user_id' => $this->admin->id,
            'monto' => 50000,
            'fecha' => $fechaAbono,
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono 1',
        ]);

        // Cerramos el día del abono
        $this->cerrarDia($fechaAbono);

        // Intentar anular la orden debe rebotar con error descriptivo
        $response = $this->actingAs($this->admin)->post(route('mantenimientos.anular', $mantenimiento));
        $response->assertSessionHas('error');

        $mantenimiento->refresh();
        $this->assertFalse((bool) $mantenimiento->anulado);
    }
}
