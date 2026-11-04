<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityBloque0AuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected string $adminPassword = 'AdminSecret123*';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin Security',
            'email' => 'admin_sec0@test.com',
            'password' => Hash::make($this->adminPassword),
            'active' => true,
        ]);

        $this->tecnico = User::factory()->create([
            'role' => 'tecnico',
            'name' => 'Tecnico Security',
            'email' => 'tecnico_sec0@test.com',
            'password' => Hash::make('TecnicoSecret123*'),
            'active' => true,
        ]);
    }

    /**
     * C1/C2: Técnico requiere contraseña de administrador para actualizar una factura.
     */
    public function test_tecnico_requires_admin_password_to_update_factura(): void
    {
        $cliente = Cliente::factory()->create(['active' => true]);
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_venta' => 10000]);

        $factura = Factura::create([
            'numero_factura' => 'VT-SEC-01',
            'tipo_movimiento' => 'venta',
            'facturable_type' => Cliente::class,
            'facturable_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'total_documento' => 20000,
            'total_pagado' => 20000,
            'estado' => 'emitida',
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 10000,
            'subtotal' => 20000,
        ]);

        $updateData = [
            'fecha' => now()->toDateString(),
            'facturable_global' => 'Cliente:'.$cliente->id,
            'total_pagado' => 20000,
            'existing_items' => [
                [
                    'id' => $item->id,
                    'stock_id' => $stock->id,
                    'cantidad' => 2,
                    'precio_unitario' => 10000,
                ],
            ],
        ];

        // 1. Técnico sin contraseña -> rechaza
        $respNoPass = $this->actingAs($this->tecnico)
            ->put(route('inventario.facturas.update', $factura), $updateData);
        $respNoPass->assertSessionHas('error');
        $this->assertStringContainsString('contraseña de un administrador', session('error'));

        // 2. Técnico con contraseña equivocada -> rechaza
        $respWrongPass = $this->actingAs($this->tecnico)
            ->put(route('inventario.facturas.update', $factura), array_merge($updateData, [
                'password_confirm' => 'WrongPassword!',
            ]));
        $respWrongPass->assertSessionHas('error');
        $this->assertStringContainsString('Contraseña de administrador incorrecta', session('error'));

        // 3. Técnico con contraseña de admin correcta -> acepta
        $respCorrectPass = $this->actingAs($this->tecnico)
            ->put(route('inventario.facturas.update', $factura), array_merge($updateData, [
                'password_confirm' => $this->adminPassword,
            ]));
        $respCorrectPass->assertSessionHas('success');

        // 4. Admin directo -> no requiere contraseña
        $respAdmin = $this->actingAs($this->admin)
            ->put(route('inventario.facturas.update', $factura), $updateData);
        $respAdmin->assertSessionHas('success');
    }

    /**
     * C1: No se puede reducir el total_pagado de una factura por debajo de los abonos ya registrados en caja.
     */
    public function test_cannot_lower_factura_total_pagado_below_registered_abonos(): void
    {
        $cliente = Cliente::factory()->create(['active' => true]);
        $stock = Stock::factory()->create(['cantidad' => 10, 'precio_venta' => 50000]);

        $factura = Factura::create([
            'numero_factura' => 'VT-ABONOS-01',
            'tipo_movimiento' => 'venta',
            'facturable_type' => Cliente::class,
            'facturable_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'total_documento' => 100000,
            'total_pagado' => 70000,
            'estado' => 'pendiente_pago',
            'user_id' => $this->admin->id,
        ]);

        $item = FacturaItem::create([
            'factura_id' => $factura->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 50000,
            'subtotal' => 100000,
        ]);

        $concepto = ConceptoCaja::create(['nombre' => 'Venta Repuestos']);

        $cajaPadre = MovimientoCaja::create([
            'factura_id' => $factura->id,
            'persona' => $cliente->nombre,
            'fecha' => now()->toDateString(),
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 30000,
            'monto_total' => 100000,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Abono hijo de 40.000 (total abonos en caja = 40.000)
        MovimientoCaja::create([
            'parent_id' => $cajaPadre->id,
            'factura_id' => $factura->id,
            'persona' => $cliente->nombre,
            'fecha' => now()->toDateString(),
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 40000,
            'monto_total' => null,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        // Intentar reducir total_pagado a 20.000 (menor a los 40.000 ya ingresados)
        $resp = $this->actingAs($this->admin)
            ->put(route('inventario.facturas.update', $factura), [
                'fecha' => now()->toDateString(),
                'facturable_global' => 'Cliente:'.$cliente->id,
                'total_pagado' => 20000,
                'existing_items' => [
                    [
                        'id' => $item->id,
                        'stock_id' => $stock->id,
                        'cantidad' => 2,
                        'precio_unitario' => 50000,
                    ],
                ],
            ]);

        $resp->assertSessionHas('error');
        $this->assertStringContainsString('no puede ser menor a los abonos ya registrados', session('error'));
    }

    /**
     * A1: Técnico requiere contraseña de administrador para crear un cierre de caja.
     */
    public function test_tecnico_requires_admin_password_to_create_cierre_caja(): void
    {
        $fecha = Carbon::today()->toDateString();

        // 1. Técnico sin clave -> rechazado
        $respNoPass = $this->actingAs($this->tecnico)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'observaciones' => 'Intento de cierre sin clave',
        ]);
        $respNoPass->assertSessionHas('error');
        $this->assertNull(CierreCaja::where('fecha', $fecha)->first());

        // 2. Técnico con clave errónea -> rechazado
        $respWrongPass = $this->actingAs($this->tecnico)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'password_confirm' => 'MalasClaves123',
            'observaciones' => 'Intento de cierre con clave errónea',
        ]);
        $respWrongPass->assertSessionHas('error');
        $this->assertNull(CierreCaja::where('fecha', $fecha)->first());

        // 3. Técnico con clave correcta de admin -> éxito
        $respOk = $this->actingAs($this->tecnico)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'password_confirm' => $this->adminPassword,
            'observaciones' => 'Cierre autorizado por admin',
        ]);
        $respOk->assertRedirect(route('cierre.index'));
        $this->assertNotNull(CierreCaja::where('fecha', $fecha)->first());
    }

    /**
     * M4: Técnico requiere contraseña de administrador para editar observaciones/motivo de un cierre de caja.
     */
    public function test_tecnico_requires_admin_password_to_update_cierre_caja(): void
    {
        $cierre = CierreCaja::create([
            'fecha' => Carbon::yesterday()->toDateString(),
            'total_ingresos' => 100000,
            'total_egresos' => 20000,
            'efectivo' => 80000,
            'efectivo_real_contado' => 80000,
            'diferencia' => 0,
            'consignacion' => 0,
            'saldo_final' => 80000,
            'num_movimientos' => 2,
            'bloqueado' => true,
            'observaciones' => 'Observación original',
            'user_id' => $this->admin->id,
        ]);

        // 1. Técnico sin contraseña -> rechaza
        $respNoPass = $this->actingAs($this->tecnico)->put(route('cierre.update', $cierre), [
            'observaciones' => 'Observación alterada sin permiso',
            'motivo_diferencia' => 'Falso descuadre',
        ]);
        $respNoPass->assertSessionHas('error');
        $this->assertEquals('Observación original', $cierre->fresh()->observaciones);

        // 2. Técnico con contraseña de admin -> acepta
        $respOk = $this->actingAs($this->tecnico)->put(route('cierre.update', $cierre), [
            'observaciones' => 'Observación autorizada',
            'motivo_diferencia' => 'Aclaración autorizada',
            'password_confirm' => $this->adminPassword,
        ]);
        $respOk->assertRedirect(route('cierre.show', $cierre->id));
        $this->assertEquals('Observación autorizada', $cierre->fresh()->observaciones);
    }

    /**
     * A2: Técnico requiere contraseña de administrador para modificar un movimiento de caja.
     */
    public function test_tecnico_requires_admin_password_to_update_movimiento_caja(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Honorarios']);

        $movimiento = MovimientoCaja::create([
            'persona' => 'Proveedor Original',
            'fecha' => now()->toDateString(),
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'egreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $updateData = [
            'persona' => 'Proveedor Modificado',
            'fecha' => now()->toDateString(),
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'egreso',
            'tipo_pago' => 'efectivo',
            'monto' => 99999,
        ];

        // 1. Técnico sin clave -> rechaza
        $respNoPass = $this->actingAs($this->tecnico)->put(route('caja.update', $movimiento), $updateData);
        $respNoPass->assertSessionHas('error');
        $this->assertEquals(50000, (float) $movimiento->fresh()->monto);

        // 2. Técnico con clave admin -> actualiza
        $respOk = $this->actingAs($this->tecnico)->put(route('caja.update', $movimiento), array_merge($updateData, [
            'password_confirm' => $this->adminPassword,
        ]));
        $respOk->assertRedirect(route('caja.index'));
        $this->assertEquals(99999, (float) $movimiento->fresh()->monto);
    }

    /**
     * A3: Actualización de stock ignora cantidad_original enviada por el cliente y calcula diff contra DB bloqueada.
     */
    public function test_stock_update_ignores_client_cantidad_original_and_uses_db_quantity(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900987654-'.rand(100, 999),
            'nombre_razon_social' => 'Proveedor Test SAS',
            'tipo_identificacion' => 'nit',
            'active' => true,
        ]);

        $stock = Stock::factory()->create([
            'proveedor_id' => $proveedor->id,
            'producto' => 'Disco SSD 1TB',
            'cantidad' => 10,
            'precio_compra' => 200000,
            'precio_venta' => 300000,
            'precio_tecnico' => 250000,
        ]);

        // El cliente envía maliciosamente cantidad_original = 0 y nueva cantidad = 15
        // Si el servidor confiara en el cliente, sumaría 15-0 = 15 unidades extra (stock total = 25).
        // Al calcularse contra la BD (10 unidades), debe sumar 15-10 = 5 unidades extra (stock total = 15).
        $response = $this->actingAs($this->admin)->put(route('stocks.update', $stock), [
            'producto' => 'Disco SSD 1TB Editado',
            'codigo' => $stock->codigo,
            'categoria' => 'Almacenamiento',
            'subcategoria' => 'SSD',
            'cantidad' => 15,
            'cantidad_original' => 0, // Inyección maliciosa
            'proveedor_id' => $proveedor->id,
            'precio_compra' => 200000,
            'utilidad' => 50,
            'precio_venta' => 300000,
            'precio_tecnico' => 250000,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('stocks.index'));
        $this->assertEquals(15, (int) $stock->fresh()->cantidad);
    }

    /**
     * M1: No se puede anular una cotización que ya ha sido convertida en factura.
     */
    public function test_cannot_anular_converted_cotizacion(): void
    {
        $cliente = Cliente::factory()->create(['active' => true]);

        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-CONVERTIDA-01',
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'total' => 150000,
            'estado' => 'convertida',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $resp = $this->actingAs($this->admin)->post(route('cotizaciones.anular', $cotizacion));
        $resp->assertSessionHas('error');
        $this->assertStringContainsString('ya ha sido convertida en factura', session('error'));
        $this->assertFalse($cotizacion->fresh()->anulado);
    }
}
