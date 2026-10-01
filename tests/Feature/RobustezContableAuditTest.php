<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RobustezContableAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cliente $cliente;
    private Tecnico $tecnico;
    private Equipo $equipo;
    private ConceptoCaja $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Auditoria',
            'email' => 'admin_audit@test.com',
            'password' => Hash::make('Admin123*'),
            'role' => 'admin',
            'active' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'identificacion' => '10203040',
            'movil' => '3001234567',
            'email' => 'carlos@test.com',
            'active' => true,
        ]);

        $this->tecnico = Tecnico::create([
            'nombre' => 'Tecnico Juan',
            'identificacion' => '99887766',
            'especialidad' => 'Hardware',
            'movil' => '3109876543',
            'email' => 'juan@tecnisystemas.com',
            'active' => true,
        ]);

        $this->equipo = Equipo::create([
            'nombre' => 'Laptop Dell Inspiron',
            'marca' => 'Dell',
            'modelo' => '5520',
            'serie' => 'DELL-98765',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'active' => true,
        ]);

        $this->concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Cobro de Servicios']);
    }

    /**
     * P0-B: Rechazar abonos sobre registros de Mantenimiento anulados.
     */
    public function test_p0_b_no_se_pueden_registrar_abonos_a_mantenimiento_anulado(): void
    {
        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnico->id,
            'user_id' => $this->admin->id,
            'id_orden' => 'ORD-TEST-001',
            'tipo' => 'correctivo',
            'descripcion' => 'Revisión por fallo de teclado',
            'reparacion' => 'Cambio de teclado',
            'fecha_entrada' => now()->toDateString(),
            'costo' => 150000,
            'estado' => 'entregado',
            'anulado' => true, // Anulado
        ]);

        $response = $this->actingAs($this->admin)->post(route('abonos.store', $mantenimiento), [
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Abono indebido a orden anulada',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('abonos', [
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
        ]);
    }

    /**
     * P0-B: Rechazar abonos sobre MovimientoCaja anulado o Factura anulada.
     */
    public function test_p0_b_no_se_pueden_registrar_abonos_a_movimiento_o_factura_anulada(): void
    {
        $movimiento = MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'monto_total' => 150000,
            'estado' => 'anulado',
            'anulado' => true,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('caja.abonos.store', $movimiento), [
            'monto_abono' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('movimiento_cajas', [
            'parent_id' => $movimiento->id,
            'monto' => 50000,
        ]);
    }

    /**
     * P0-D: Anular movimiento en caja revierte el Abono vinculado y actualiza el saldo del mantenimiento.
     */
    public function test_p0_d_anular_movimiento_caja_sincroniza_abono_asociado_y_recalcula_saldo(): void
    {
        $mantenimiento = Mantenimiento::create([
            'equipo_id' => $this->equipo->id,
            'tecnico_id' => $this->tecnico->id,
            'user_id' => $this->admin->id,
            'id_orden' => 'ORD-TEST-002',
            'tipo' => 'preventivo',
            'descripcion' => 'Mantenimiento preventivo periódico',
            'reparacion' => 'Mantenimiento preventivo general',
            'fecha_entrada' => now()->toDateString(),
            'costo' => 200000,
            'estado' => 'en_reparacion',
            'anulado' => false,
        ]);

        // Registrar abono legítimo a través del controlador
        $this->actingAs($this->admin)->post(route('abonos.store', $mantenimiento), [
            'monto' => 80000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'descripcion' => 'Anticipo inicial',
        ]);

        $mantenimiento->refresh();
        $this->assertEquals(80000, $mantenimiento->total_abonado);
        $this->assertEquals(120000, $mantenimiento->saldo_pendiente);

        $abono = $mantenimiento->abonos()->first();
        $this->assertNotNull($abono);
        $this->assertFalse($abono->anulado);

        // Buscar el movimiento de caja generado
        $movCaja = MovimientoCaja::where('abono_id', $abono->id)->first();
        $this->assertNotNull($movCaja);

        // Anular el movimiento en Caja
        $responseAnular = $this->actingAs($this->admin)->post(route('caja.anular', $movCaja));
        $responseAnular->assertSessionHas('success');

        // Verificar que el Abono se marcó como anulado y el saldo del mantenimiento se actualizó
        $abono->refresh();
        $this->assertTrue($abono->anulado);

        $mantenimiento->refresh();
        $this->assertEquals(0, $mantenimiento->total_abonado);
        $this->assertEquals(200000, $mantenimiento->saldo_pendiente);
    }

    /**
     * P0-C: Convertir cotización a venta bloquea doble-clic, decrementa stock y asigna factura_id.
     */
    public function test_p0_c_convertir_cotizacion_crea_factura_con_factura_id_y_bloquea_reconversion(): void
    {
        $stock = Stock::create([
            'producto' => 'Monitor 24 IPS',
            'categoria' => 'Hardware',
            'cantidad' => 5,
            'precio_venta' => 450000,
            'active' => true,
        ]);

        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-001',
            'cliente_id' => $this->cliente->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'total' => 450000,
            'estado' => 'pendiente',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        CotizacionItem::create([
            'cotizacion_id' => $cotizacion->id,
            'tipo' => 'stock',
            'item_id' => $stock->id,
            'descripcion' => 'Monitor 24 IPS',
            'cantidad' => 2,
            'precio_unitario' => 225000,
            'subtotal' => 450000,
        ]);

        // Primera conversión: éxito
        $response1 = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cotizacion));
        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $stock->refresh();
        $this->assertEquals(3, $stock->cantidad); // Descontó 2 unidades

        $cotizacion->refresh();
        $this->assertEquals('aprobada', $cotizacion->estado);

        // Movimiento de caja tiene factura_id vinculada
        $movCaja = MovimientoCaja::where('monto_total', 450000)->first();
        $this->assertNotNull($movCaja);
        $this->assertNotNull($movCaja->factura_id);

        // Segunda conversión inmediata (intento de doble submit): bloqueado sin descontar stock nuevamente
        $response2 = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cotizacion));
        $response2->assertSessionHas('error');

        $stock->refresh();
        $this->assertEquals(3, $stock->cantidad); // Sigue en 3
    }

    /**
     * P0-1 & P1-6: Factura recalcularPagos prioriza factura_id y no colisiona con prefijos regex.
     */
    public function test_p0_1_factura_id_foreign_key_y_recalcular_pagos_exacto_sin_colision_regex(): void
    {
        // Factura 1 (#VT-1) y Factura 10 (#VT-10)
        $factura1 = Factura::create([
            'numero_factura' => 'VT-1',
            'tipo_movimiento' => 'venta',
            'estado' => 'pendiente_pago',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 100000,
            'total_pagado' => 0,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $factura10 = Factura::create([
            'numero_factura' => 'VT-10',
            'tipo_movimiento' => 'venta',
            'estado' => 'pendiente_pago',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 500000,
            'total_pagado' => 0,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        // Movimiento para Factura 10 con monto 500.000
        MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 500000,
            'monto_total' => 500000,
            'descripcion' => 'Cobro venta #VT-10',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'factura_id' => $factura10->id,
        ]);

        // Movimiento para Factura 1 con monto 40.000
        MovimientoCaja::create([
            'persona' => 'Cliente Test',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 40000,
            'monto_total' => 100000,
            'descripcion' => 'Cobro venta #VT-1',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'factura_id' => $factura1->id,
        ]);

        // Recalcular pagos en ambas
        $factura1->recalcularPagos();
        $factura10->recalcularPagos();

        // VT-1 solo debe tener 40.000 (no contaminado por los 500.000 de VT-10)
        $this->assertEquals(40000, (float) $factura1->total_pagado);
        $this->assertEquals(60000, (float) $factura1->saldo_pendiente);

        // VT-10 tiene exactamente 500.000
        $this->assertEquals(500000, (float) $factura10->total_pagado);
        $this->assertEquals(0, (float) $factura10->saldo_pendiente);
    }

    /**
     * P0-A: updateFactura sincroniza caja con factura_id y respeta abonos existentes.
     */
    public function test_p0_a_update_factura_con_abonos_no_destruye_abonos_en_caja(): void
    {
        $stock = Stock::create([
            'producto' => 'Teclado Mecánico',
            'categoria' => 'Hardware',
            'cantidad' => 10,
            'precio_venta' => 150000,
            'active' => true,
        ]);

        $factura = Factura::create([
            'numero_factura' => 'VT-TECLADO',
            'tipo_movimiento' => 'venta',
            'estado' => 'pendiente_pago',
            'facturable_id' => $this->cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 300000,
            'total_pagado' => 100000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 150000,
        ]);

        // Movimiento base de caja (pago inicial 100.000)
        $movBase = MovimientoCaja::create([
            'persona' => 'Carlos Mendoza',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'monto_total' => 300000,
            'descripcion' => 'Cobro venta #VT-TECLADO',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'factura_id' => $factura->id,
        ]);

        // Registrar un abono de 50.000 hijo
        $abonoHijo = MovimientoCaja::create([
            'persona' => 'Carlos Mendoza',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'monto_total' => 0,
            'descripcion' => 'Abono parcial a #VT-TECLADO',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'parent_id' => $movBase->id,
            'factura_id' => $factura->id,
        ]);

        $factura->recalcularPagos();
        $this->assertEquals(150000, (float) $factura->total_pagado);

        // Editar factura actualizando total pagado a 200.000 (nuevo abono o ajuste)
        $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), [
            'fecha' => now()->toDateString(),
            'total_pagado' => '200.000',
            'facturable_global' => "Cliente:{$this->cliente->id}",
            'existing_items' => [
                [
                    'id' => $item->id,
                    'stock_id' => $stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => '150.000',
                ],
            ],
        ]);

        $factura->refresh();
        $this->assertEquals(200000, (float) $factura->total_pagado);
        $this->assertEquals(100000, (float) $factura->saldo_pendiente);

        // El abono hijo de 50.000 sigue intacto y no fue borrado
        $abonoHijo->refresh();
        $this->assertFalse($abonoHijo->anulado);
        $this->assertEquals(50000, (float) $abonoHijo->monto);

        // El monto base se ajustó a 150.000 (150.000 + 50.000 = 200.000)
        $movBase->refresh();
        $this->assertEquals(150000, (float) $movBase->monto);
    }

    /**
     * P0-2: AdminUserSeeder utiliza firstOrCreate y no sobreescribe credenciales existentes.
     */
    public function test_p0_2_admin_user_seeder_no_destruye_credenciales_existentes(): void
    {
        $existingAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'administrador@tecnisystemas.com',
            'password' => Hash::make('MiClavePersonalizada123*'),
            'role' => 'admin',
            'active' => true,
        ]);

        // Ejecutar seeder
        $this->seed(AdminUserSeeder::class);

        // Verificar que la contraseña no fue alterada por el seeder
        $existingAdmin->refresh();
        $this->assertTrue(Hash::check('MiClavePersonalizada123*', $existingAdmin->password));
        $this->assertEquals('Super Administrador', $existingAdmin->name);
    }
}
