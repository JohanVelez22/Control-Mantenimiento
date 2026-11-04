<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnuladoSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Security Test',
            'email' => 'admin_sec@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'active' => true,
        ]);
    }

    public function test_cliente_anulado_cannot_be_edited_or_updated(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Cliente Inactivo',
            'apellidos' => 'Prueba',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => 'CC-INACT-01',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3000000000',
            'active' => false,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('clientes.edit', $cliente));
        $resEdit->assertRedirect(route('clientes.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('clientes.update', $cliente), [
            'nombres' => 'Nombre Modificado',
            'apellidos' => 'Prueba',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => 'CC-INACT-01',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3000000000',
        ]);
        $resUpdate->assertRedirect(route('clientes.index'));
        $resUpdate->assertSessionHas('error');

        $cliente->refresh();
        $this->assertEquals('Cliente Inactivo', $cliente->nombres);
    }

    public function test_proveedor_anulado_cannot_be_edited_or_updated(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'tipo_identificacion' => 'nit',
            'identificacion' => 'NIT-INACT-01',
            'nombre_razon_social' => 'Proveedor Inactivo',
            'active' => false,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('proveedores.edit', $proveedor));
        $resEdit->assertRedirect(route('proveedores.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('proveedores.update', $proveedor), [
            'tipo_entidad' => 'empresa',
            'tipo_identificacion' => 'nit',
            'identificacion' => 'NIT-INACT-01',
            'nombre_razon_social' => 'Proveedor Modificado',
        ]);
        $resUpdate->assertRedirect(route('proveedores.index'));
        $resUpdate->assertSessionHas('error');

        $proveedor->refresh();
        $this->assertEquals('Proveedor Inactivo', $proveedor->nombre_razon_social);
    }

    public function test_tecnico_anulado_cannot_be_edited_or_updated(): void
    {
        $tecnico = Tecnico::create([
            'nombre' => 'Tecnico Inactivo',
            'identificacion' => 'TEC-INACT-01',
            'especialidad' => 'Hardware',
            'movil' => '3001112233',
            'active' => false,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('tecnicos.edit', $tecnico));
        $resEdit->assertRedirect(route('tecnicos.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('tecnicos.update', $tecnico), [
            'nombre' => 'Tecnico Modificado',
            'identificacion' => 'TEC-INACT-01',
            'especialidad' => 'Hardware',
            'movil' => '3001112233',
        ]);
        $resUpdate->assertRedirect(route('tecnicos.index'));
        $resUpdate->assertSessionHas('error');

        $tecnico->refresh();
        $this->assertEquals('Tecnico Inactivo', $tecnico->nombre);
    }

    public function test_usuario_anulado_cannot_be_edited_or_updated(): void
    {
        $usuario = User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'user_inact@test.com',
            'password' => Hash::make('password'),
            'role' => 'tecnico',
            'active' => false,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('usuarios.edit', $usuario));
        $resEdit->assertRedirect(route('usuarios.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('usuarios.update', $usuario), [
            'name' => 'Usuario Modificado',
            'email' => 'user_inact@test.com',
            'role' => 'tecnico',
        ]);
        $resUpdate->assertRedirect(route('usuarios.index'));
        $resUpdate->assertSessionHas('error');

        $usuario->refresh();
        $this->assertEquals('Usuario Inactivo', $usuario->name);
    }

    public function test_movimiento_caja_anulado_cannot_be_edited_or_updated(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Concepto Caja Test']);
        $mov = MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Persona Test',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'anulado',
            'anulado' => true,
            'user_id' => $this->admin->id,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('caja.edit', $mov));
        $resEdit->assertRedirect(route('caja.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('caja.update', $mov), [
            'persona' => 'Persona Modificada',
            'fecha' => Carbon::today()->toDateString(),
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 70000,
        ]);
        $resUpdate->assertRedirect(route('caja.index'));
        $resUpdate->assertSessionHas('error');

        $mov->refresh();
        $this->assertEquals(50000, $mov->monto);
    }

    public function test_mantenimiento_anulado_cannot_be_edited_or_updated(): void
    {
        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnico = Tecnico::factory()->create();

        $mant = Mantenimiento::factory()->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
            'user_id' => $this->admin->id,
            'anulado' => true,
            'estado' => 'pendiente',
            'costo' => 100000,
            'descripcion' => 'Mantenimiento Original',
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('mantenimientos.edit', $mant));
        $resEdit->assertRedirect(route('mantenimientos.show', $mant));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('mantenimientos.update', $mant), [
            'fecha_entrada' => now()->toDateString(),
            'tipo' => 'preventivo',
            'reparacion' => 'software',
            'descripcion' => 'Descripcion Modificada',
            'costo' => 150000,
            'estado' => 'pendiente',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
        ]);
        $resUpdate->assertRedirect(route('mantenimientos.show', $mant));
        $resUpdate->assertSessionHas('error');

        $mant->refresh();
        $this->assertEquals('Mantenimiento Original', $mant->descripcion);
    }

    public function test_electronica_anulada_cannot_be_edited_or_updated(): void
    {
        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnico = Tecnico::factory()->create();

        $elec = Electronica::create([
            'id_orden' => 'ELC-TEST-001',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
            'user_id' => $this->admin->id,
            'anulado' => true,
            'estado' => 'pendiente',
            'costo' => 80000,
            'descripcion_problema' => 'Falla Electronica Original',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'fecha_entrada' => now()->toDateString(),
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('electronicas.edit', $elec));
        $resEdit->assertRedirect(route('electronicas.show', $elec));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('electronicas.update', $elec), [
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
            'descripcion_problema' => 'Descripcion Modificada',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 90000,
            'estado' => 'pendiente',
            'fecha_entrada' => now()->toDateString(),
        ]);
        $resUpdate->assertRedirect(route('electronicas.show', $elec));
        $resUpdate->assertSessionHas('error');

        $elec->refresh();
        $this->assertEquals('Falla Electronica Original', $elec->descripcion_problema);
    }

    public function test_factura_anulada_cannot_be_edited_or_updated(): void
    {
        $cliente = Cliente::factory()->create();
        $factura = Factura::create([
            'numero_factura' => Factura::siguienteNumero('F'),
            'tipo_movimiento' => 'venta',
            'estado' => 'anulada',
            'facturable_type' => Cliente::class,
            'facturable_id' => $cliente->id,
            'total_documento' => 50000,
            'total_pagado' => 50000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('inventario.facturas.edit', $factura));
        $resEdit->assertRedirect(route('inventario.facturas.show', $factura));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('inventario.facturas.update', $factura), [
            'fecha' => now()->toDateString(),
            'total_pagado' => 60000,
        ]);
        $resUpdate->assertRedirect(route('inventario.facturas.show', $factura));
        $resUpdate->assertSessionHas('error');
    }

    public function test_stock_anulado_cannot_be_edited_or_updated(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'tipo_identificacion' => 'nit',
            'identificacion' => 'NIT-STK-01',
            'nombre_razon_social' => 'Proveedor Repuestos',
            'active' => true,
        ]);
        $stock = Stock::create([
            'codigo' => 'STK-INACT-01',
            'producto' => 'Producto Inactivo',
            'categoria' => 'Repuestos',
            'subcategoria' => 'Pantallas',
            'cantidad' => 10,
            'precio_compra' => 50000,
            'utilidad' => 30,
            'proveedor_id' => $proveedor->id,
            'active' => false,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('stocks.edit', $stock));
        $resEdit->assertRedirect(route('stocks.index'));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('stocks.update', $stock), [
            'codigo' => 'STK-INACT-01',
            'producto' => 'Producto Modificado',
            'categoria' => 'Repuestos',
            'subcategoria' => 'Pantallas',
            'cantidad' => 10,
            'precio_compra' => 50000,
            'utilidad' => 30,
            'proveedor_id' => $proveedor->id,
        ]);
        $resUpdate->assertRedirect(route('stocks.index'));
        $resUpdate->assertSessionHas('error');

        $stock->refresh();
        $this->assertEquals('Producto Inactivo', $stock->producto);
    }

    public function test_stock_anulado_cannot_be_dado_de_baja(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'tipo_identificacion' => 'nit',
            'identificacion' => 'NIT-STK-02',
            'nombre_razon_social' => 'Proveedor Teclados',
            'active' => true,
        ]);
        $stock = Stock::create([
            'codigo' => 'STK-BAJA-INACT-01',
            'producto' => 'Producto Para Baja Inactivo',
            'categoria' => 'Repuestos',
            'subcategoria' => 'Teclados',
            'cantidad' => 5,
            'precio_compra' => 20000,
            'utilidad' => 30,
            'proveedor_id' => $proveedor->id,
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stocks.dar-de-baja', $stock), [
            'cantidad' => 2,
            'motivo' => 'defectuoso_fabrica',
            'observacion' => 'Prueba de merma en inactivo',
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $stock->refresh();
        $this->assertEquals(5, $stock->cantidad);
    }

    public function test_cotizacion_anulada_cannot_be_edited_or_updated(): void
    {
        $cliente = Cliente::factory()->create();
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-SEC-01',
            'cliente_id' => $cliente->id,
            'user_id' => $this->admin->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'estado' => 'pendiente',
            'anulado' => true,
            'total' => 100000,
        ]);

        $resEdit = $this->actingAs($this->admin)->get(route('cotizaciones.edit', $cotizacion));
        $resEdit->assertRedirect(route('cotizaciones.show', $cotizacion));
        $resEdit->assertSessionHas('error');

        $resUpdate = $this->actingAs($this->admin)->put(route('cotizaciones.update', $cotizacion), [
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'items' => [
                [
                    'tipo' => 'libre',
                    'descripcion' => 'Item Modificado',
                    'cantidad' => 1,
                    'precio_unitario' => 120000,
                ],
            ],
        ]);
        $resUpdate->assertSessionHas('error');
    }
}
