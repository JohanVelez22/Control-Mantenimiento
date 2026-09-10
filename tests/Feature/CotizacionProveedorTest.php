<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Equipo;
use App\Models\Cotizacion;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CotizacionProveedorTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Cliente $cliente;
    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $this->admin = User::first() ?? User::factory()->create(['role' => 'admin']);

        $this->cliente = Cliente::firstOrCreate(
            ['identificacion' => 'TESTCLI999'],
            [
                'nombres' => 'Carlos',
                'apellidos' => 'Prueba',
                'tipo_identificacion' => 'cedula_ciudadania',
                'tipo_cliente' => 'cliente',
                'movil' => '3001234567',
                'active' => true,
            ]
        );

        $this->proveedor = Proveedor::firstOrCreate(
            ['identificacion' => 'TESTPROV999'],
            [
                'nombre_razon_social' => 'Distribuciones Alfa SAS',
                'tipo_entidad' => 'empresa',
                'tipo_identificacion' => 'NIT',
                'telefono' => '3109876543',
                'active' => true,
            ]
        );
    }

    public function test_cotizacion_accessors_for_proveedor_and_cliente()
    {
        $cotCli = new Cotizacion([
            'codigo' => 'COT-CLI-1',
            'cliente_id' => $this->cliente->id,
            'total' => 100000,
        ]);
        $cotCli->setRelation('cliente', $this->cliente);

        $this->assertEquals('cliente', $cotCli->destinatario_tipo);
        $this->assertEquals($this->cliente->nombre, $cotCli->destinatario_nombre);
        $this->assertStringContainsString('👤', $cotCli->destinatario_label);

        $cotProv = new Cotizacion([
            'codigo' => 'COT-PROV-1',
            'proveedor_id' => $this->proveedor->id,
            'total' => 200000,
        ]);
        $cotProv->setRelation('proveedor', $this->proveedor);

        $this->assertEquals('proveedor', $cotProv->destinatario_tipo);
        $this->assertEquals('Distribuciones Alfa SAS', $cotProv->destinatario_nombre);
        $this->assertStringContainsString('🏢', $cotProv->destinatario_label);
    }

    public function test_equipo_accessors_for_proveedor_and_cliente()
    {
        $eqCli = new Equipo([
            'nombre' => 'PC Oficina',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'serie' => 'SN-CLI-01',
            'cliente_id' => $this->cliente->id,
        ]);
        $eqCli->setRelation('cliente', $this->cliente);

        $this->assertEquals('cliente', $eqCli->propietario_tipo);
        $this->assertEquals($this->cliente->nombre, $eqCli->propietario_nombre);
        $this->assertStringContainsString('👤', $eqCli->propietario_label);

        $eqProv = new Equipo([
            'nombre' => 'Servidor Proveedor',
            'marca' => 'HP',
            'modelo' => 'ProLiant',
            'serie' => 'SN-PROV-01',
            'proveedor_id' => $this->proveedor->id,
        ]);
        $eqProv->setRelation('proveedor', $this->proveedor);

        $this->assertEquals('proveedor', $eqProv->propietario_tipo);
        $this->assertEquals('Distribuciones Alfa SAS', $eqProv->propietario_nombre);
        $this->assertStringContainsString('🏢', $eqProv->propietario_label);
    }

    public function test_crear_cotizacion_con_proveedor_via_facturable_global()
    {
        $response = $this->actingAs($this->admin)->post(route('cotizaciones.store'), [
            'facturable_global' => 'Proveedor:' . $this->proveedor->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'notas' => 'Cotización de prueba proveedor',
            'items' => [
                [
                    'tipo' => 'libre',
                    'descripcion' => 'Servicio de mantenimiento especial',
                    'cantidad' => 2,
                    'precio_unitario' => 50000,
                ]
            ]
        ]);

        $response->assertRedirect(route('cotizaciones.index'));

        $cot = Cotizacion::where('proveedor_id', $this->proveedor->id)->latest('id')->first();
        $this->assertNotNull($cot);
        $this->assertNull($cot->cliente_id);
        $this->assertEquals(100000, (float)$cot->total);
        $this->assertEquals('proveedor', $cot->destinatario_tipo);
        $this->assertEquals('Distribuciones Alfa SAS', $cot->destinatario_nombre);
    }

    public function test_crear_equipo_con_proveedor_via_propietario_global()
    {
        $serie = 'TEST-EQ-' . uniqid();
        $response = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'Laptop Garantía',
            'marca' => 'Lenovo',
            'modelo' => 'ThinkPad',
            'serie' => $serie,
            'propietario_global' => 'Proveedor:' . $this->proveedor->id,
            'observacion' => 'Equipo de proveedor para revisión',
        ]);

        $response->assertRedirect(route('equipos.index'));

        $equipo = Equipo::where('serie', strtoupper($serie))->first();
        $this->assertNotNull($equipo);
        $this->assertEquals($this->proveedor->id, $equipo->proveedor_id);
        $this->assertNull($equipo->cliente_id);
        $this->assertEquals('Distribuciones Alfa SAS', $equipo->propietario_nombre);
    }

    public function test_convertir_cotizacion_proveedor_a_factura()
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-CONV-1',
            'proveedor_id' => $this->proveedor->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'total' => 150000,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
        ]);

        \App\Models\CotizacionItem::create([
            'cotizacion_id' => $cotizacion->id,
            'tipo' => 'libre',
            'descripcion' => 'Revisión técnica de placas',
            'cantidad' => 1,
            'precio_unitario' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cotizacion));
        $response->assertRedirect();

        $cotizacion->refresh();
        $this->assertEquals('aprobada', $cotizacion->estado);

        $factura = \App\Models\Factura::where('facturable_type', Proveedor::class)
            ->where('facturable_id', $this->proveedor->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($factura);
        $this->assertEquals(150000, (float)$factura->total_documento);
    }

    public function test_vistas_mantenimiento_y_electronica_con_equipo_proveedor()
    {
        $serie = 'TEST-EQ-VIEW-' . uniqid();
        $equipo = Equipo::create([
            'nombre' => 'Switch Administrable',
            'marca' => 'Cisco',
            'modelo' => 'Catalyst 2960',
            'serie' => $serie,
            'proveedor_id' => $this->proveedor->id,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals('proveedor', $equipo->propietario_tipo);
        $this->assertEquals('Distribuciones Alfa SAS', $equipo->propietario_nombre);
        $this->assertStringContainsString('🏢 Proveedor:', $equipo->propietario_label);
    }
}
