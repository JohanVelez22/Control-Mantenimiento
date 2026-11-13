<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flujo completo de un día de trabajo, ejecutado SOLO por HTTP (las mismas rutas que usa la
 * interfaz), con cifras esperadas calculadas a mano e independientemente del código de la
 * aplicación. Verifica que órdenes, abonos, ventas, compras, gastos, cierre de caja y reporte
 * diario cuadren entre sí.
 *
 * Escenario (día D = 2026-03-10; saldo físico contado el día anterior = 100.000):
 *
 *   Mov. | Operación                                   | Tipo    | Medio        | Monto
 *   -----+---------------------------------------------+---------+--------------+---------
 *    1   | Abono parcial orden ORD (mano obra+repuesto) | ingreso | efectivo     |  80.000
 *    2   | Pago final de la orden                       | ingreso | consignación | 120.000
 *    3   | Venta VT: 2 x RAM a 80.000 (paga 100.000)   | ingreso | efectivo     | 100.000
 *    4   | Compra CP: 4 x RAM a 50.000 (paga todo)      | egreso  | efectivo     | 200.000
 *    5   | Gasto papelería                              | egreso  | efectivo     |  30.000
 *    X   | Ingreso manual 999.000 ANULADO               | (no cuenta)
 *
 *   Orden: mano de obra 50.000 + 1 pantalla a 150.000 = 200.000 → saldo 0 tras 1 y 2.
 *   Ingresos = 80.000 + 120.000 + 100.000 = 300.000
 *   Egresos  = 200.000 + 30.000            = 230.000
 *   Efectivo neto del día = 80.000 + 100.000 − 200.000 − 30.000 = −50.000
 *   Consignación neta     = 120.000
 *   Efectivo esperado en caja = 100.000 (inicial) − 50.000 = 50.000
 *   Facturado = orden 200.000 + venta 160.000 = 360.000; compras 200.000; balance operativo 160.000
 *   Por cobrar = saldo de la venta 60.000; por pagar = 0
 *   Stock: pantalla 10 − 1 = 9; RAM 5 − 2 + 4 = 7
 */
class DiaDeTrabajoTest extends TestCase
{
    use RefreshDatabase;

    private const DIA = '2026-03-10';

    private const DIA_ANTERIOR = '2026-03-09';

    public function test_dia_de_trabajo_completo_cuadra_caja_cierre_y_reporte(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 10)->setTime(10, 0));

        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $tecnico = Tecnico::factory()->create();
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '900123456',
            'nombre_razon_social' => 'Distribuidora Andina SAS', 'telefono' => '3001234567', 'active' => true,
        ]);
        $pantalla = Stock::create([
            'codigo' => 'PAN-01', 'producto' => 'Pantalla 15.6', 'categoria' => 'Repuestos', 'cantidad' => 10,
            'precio_compra' => 100000, 'utilidad' => 50, 'precio_venta' => 150000, 'precio_tecnico' => 120000, 'active' => true,
        ]);
        $ram = Stock::create([
            'codigo' => 'RAM-08', 'producto' => 'Memoria RAM 8GB', 'categoria' => 'Repuestos', 'cantidad' => 5,
            'precio_compra' => 50000, 'utilidad' => 60, 'precio_venta' => 80000, 'precio_tecnico' => 70000, 'active' => true,
        ]);

        // Día anterior cerrado con conteo físico real de 100.000 (saldo inicial de D).
        CierreCaja::create([
            'fecha' => self::DIA_ANTERIOR, 'total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0,
            'efectivo_real_contado' => 100000, 'diferencia' => 0, 'consignacion' => 0, 'saldo_final' => 0,
            'num_movimientos' => 0, 'bloqueado' => true, 'user_id' => $admin->id,
        ]);

        $this->actingAs($admin);

        // 1. Cliente
        $this->post(route('clientes.store'), [
            'nombres' => 'Laura', 'apellidos' => 'Gómez Restrepo', 'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '1088123456', 'genero' => 'femenino', 'tipo_cliente' => 'cliente', 'movil' => '3109876543',
        ])->assertSessionHasNoErrors();
        $cliente = Cliente::where('identificacion', '1088123456')->firstOrFail();

        // 2. Equipo
        $this->post(route('equipos.store'), [
            'nombre' => 'Portátil Lenovo', 'marca' => 'Lenovo', 'modelo' => 'IdeaPad 3', 'serie' => 'LNV-DIA-001',
            'propietario_global' => 'Cliente:'.$cliente->id,
        ])->assertSessionHasNoErrors();
        $equipo = Equipo::where('serie', 'LNV-DIA-001')->firstOrFail();
        $this->assertSame($cliente->id, (int) $equipo->cliente_id);

        // 3. Orden de mantenimiento (mano de obra 50.000)
        $this->post(route('mantenimientos.store'), [
            'fecha_entrada' => self::DIA, 'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'descripcion' => 'Cambio de pantalla', 'costo' => 50000, 'estado' => 'pendiente',
            'equipo_id' => $equipo->id, 'tecnico_id' => $tecnico->id,
        ])->assertSessionHasNoErrors();
        $orden = Mantenimiento::where('equipo_id', $equipo->id)->firstOrFail();

        // 4. Repuesto: 1 pantalla a precio de venta (cliente normal) → costo 200.000
        $this->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $pantalla->id, 'cantidad' => 1])
            ->assertSessionHas('success');
        $this->assertEquals(200000, (float) $orden->fresh()->costo);

        // 5. Abono parcial en efectivo y 6. pago final por consignación
        $this->post(route('abonos.store', $orden), ['monto' => 80000, 'fecha' => self::DIA, 'tipo_pago' => 'efectivo'])
            ->assertSessionHas('success');
        $this->post(route('abonos.store', $orden), ['monto' => 120000, 'fecha' => self::DIA, 'tipo_pago' => 'consignacion'])
            ->assertSessionHas('success');
        $this->assertEquals(0, $orden->fresh()->saldo_pendiente);
        $this->assertSame(2, Abono::where('mantenimiento_id', $orden->id)->count());

        // Un tercer abono (doble clic) debe rechazarse: no queda saldo
        $this->post(route('abonos.store', $orden), ['monto' => 120000, 'fecha' => self::DIA, 'tipo_pago' => 'consignacion'])
            ->assertSessionHas('error');
        $this->assertSame(2, Abono::where('mantenimiento_id', $orden->id)->count());

        // 7. Venta a crédito parcial: 2 RAM a 80.000 = 160.000; paga 100.000 en efectivo
        $this->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$cliente->id, 'fecha' => self::DIA, 'total_pagado' => 100000,
            'tipo_pago' => 'efectivo', 'items' => [['stock_id' => $ram->id, 'cantidad' => 2, 'precio_unitario' => 80000]],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $venta = Factura::where('tipo_movimiento', 'venta')->firstOrFail();
        $this->assertEquals(160000, (float) $venta->total_documento);
        $this->assertEquals(100000, (float) $venta->total_pagado);
        $this->assertSame('pendiente_pago', $venta->estado);

        // 8. Compra al proveedor: 4 RAM a 50.000 = 200.000, pagada en efectivo
        $this->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$proveedor->id, 'fecha' => self::DIA, 'total_pagado' => 200000,
            'tipo_pago' => 'efectivo', 'items' => [['stock_id' => $ram->id, 'cantidad' => 4, 'precio_unitario' => 50000]],
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $compra = Factura::where('tipo_movimiento', 'compra')->firstOrFail();
        $this->assertSame('emitida', $compra->estado);

        // 9. Gasto manual de papelería
        $this->post(route('caja.store'), [
            'persona' => 'Papelería El Punto', 'fecha' => self::DIA, 'nuevo_concepto' => 'Papelería',
            'tipo_movimiento' => 'egreso', 'tipo_pago' => 'efectivo', 'monto' => 30000,
        ])->assertSessionHasNoErrors();

        // 10. Ingreso erróneo que se anula: no debe contar en caja ni en cierre
        $this->post(route('caja.store'), [
            'persona' => 'Error de digitación', 'fecha' => self::DIA, 'nuevo_concepto' => 'Varios',
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 999000,
        ])->assertSessionHasNoErrors();
        $erroneo = MovimientoCaja::where('monto', 999000)->firstOrFail();
        $this->post(route('caja.anular', $erroneo), ['accion' => 'anular'])->assertSessionHas('success');
        $this->assertTrue((bool) $erroneo->fresh()->anulado);

        // Inventario
        $this->assertSame(9, (int) $pantalla->fresh()->cantidad);
        $this->assertSame(7, (int) $ram->fresh()->cantidad);

        // Movimientos de caja del día (cálculo independiente sobre las filas)
        $activos = MovimientoCaja::whereDate('fecha', self::DIA)->where('anulado', false)->where('estado', 'activo')->get();
        $this->assertCount(5, $activos);
        $this->assertEquals(300000, $activos->where('tipo_movimiento', 'ingreso')->sum('monto'));
        $this->assertEquals(230000, $activos->where('tipo_movimiento', 'egreso')->sum('monto'));

        // 11. Reporte diario ANTES del cierre
        $reporte = $this->get(route('reportes.financiero.diario', ['fecha' => self::DIA]))->assertOk();
        $resumen = $reporte->viewData('resumen');
        $esperado = [
            'total_ingresos' => 300000, 'total_egresos' => 230000, 'saldo_caja' => 70000, 'balance_neto' => 70000,
            'efectivo_ingresos' => 180000, 'efectivo_egresos' => 230000, 'efectivo_saldo' => -50000,
            'consignacion_ingresos' => 120000, 'consignacion_egresos' => 0, 'consignacion_saldo' => 120000,
            'total_mantenimientos' => 200000, 'total_electronica' => 0, 'total_ventas' => 160000, 'total_compras' => 200000,
            'total_facturado' => 360000, 'balance_operativo' => 160000,
            'saldo_pendiente_venta' => 60000, 'saldo_pendiente_compra' => 0, 'saldo_pendiente_mant' => 0,
            'saldo_pendiente_caja_ingreso' => 0, 'saldo_pendiente_caja_egreso' => 0,
            'total_por_cobrar' => 60000, 'total_por_pagar' => 0,
            'total_anulados' => 1, 'monto_anulados' => 999000,
        ];
        foreach ($esperado as $clave => $valor) {
            $this->assertEqualsWithDelta($valor, (float) $resumen[$clave], 0.001, "Reporte diario: {$clave}");
        }

        // 12. Vista previa del cierre (pantalla de cierre del día actual)
        $preview = $this->get(route('cierre.index'))->assertOk()->viewData('preview');
        $this->assertEqualsWithDelta(100000, $preview['saldo_anterior'], 0.001);
        $this->assertEqualsWithDelta(50000, $preview['efectivo_esperado'], 0.001);

        // 13. Cierre con conteo físico exacto (sin diferencia)
        $this->post(route('cierre.store'), ['fecha' => self::DIA, 'efectivo_real_contado' => '50.000'])
            ->assertSessionHas('success');
        $cierre = CierreCaja::whereDate('fecha', self::DIA)->firstOrFail();
        $this->assertEqualsWithDelta(300000, (float) $cierre->total_ingresos, 0.001);
        $this->assertEqualsWithDelta(230000, (float) $cierre->total_egresos, 0.001);
        $this->assertEqualsWithDelta(-50000, (float) $cierre->efectivo, 0.001);
        $this->assertEqualsWithDelta(120000, (float) $cierre->consignacion, 0.001);
        $this->assertEqualsWithDelta(70000, (float) $cierre->saldo_final, 0.001);
        $this->assertEqualsWithDelta(50000, (float) $cierre->efectivo_real_contado, 0.001);
        $this->assertEqualsWithDelta(0, (float) $cierre->diferencia, 0.001);
        $this->assertSame(5, (int) $cierre->num_movimientos);

        // El cierre coincide con el reporte diario
        $this->assertEqualsWithDelta((float) $resumen['total_ingresos'], (float) $cierre->total_ingresos, 0.001);
        $this->assertEqualsWithDelta((float) $resumen['total_egresos'], (float) $cierre->total_egresos, 0.001);
        $this->assertEqualsWithDelta((float) $resumen['efectivo_saldo'], (float) $cierre->efectivo, 0.001);
        $this->assertEqualsWithDelta((float) $resumen['consignacion_saldo'], (float) $cierre->consignacion, 0.001);

        // 14. Día cerrado: no admite nuevos movimientos, abonos ni segundo cierre
        $antes = MovimientoCaja::count();
        $this->post(route('caja.store'), [
            'persona' => 'Tardío', 'fecha' => self::DIA, 'nuevo_concepto' => 'Varios',
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000,
        ])->assertSessionHasErrors('fecha');
        $this->post(route('cierre.store'), ['fecha' => self::DIA, 'efectivo_real_contado' => 50000])->assertSessionHasErrors('fecha');
        $this->assertSame($antes, MovimientoCaja::count());
        $this->assertSame(1, CierreCaja::whereDate('fecha', self::DIA)->count());

        // 15. Saldo inicial del día siguiente = conteo físico de D
        $this->assertEqualsWithDelta(50000, CierreCaja::saldoInicialEfectivo('2026-03-11'), 0.001);
    }
}
