<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Proveedor;
use App\Models\CategoriaStock;
use App\Models\Stock;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Mantenimiento;
use App\Models\Tecnico;
use App\Services\StockService;

class ProductionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tecnico;
    private User $invitado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Administrador Test',
            'email' => 'admin_prod@test.com',
            'password' => Hash::make('Admin123*'),
            'role' => 'admin',
            'active' => true,
        ]);

        $this->tecnico = User::create([
            'name' => 'Tecnico Test',
            'email' => 'tecnico_prod@test.com',
            'password' => Hash::make('Tecni123*'),
            'role' => 'tecnico',
            'active' => true,
        ]);

        $this->invitado = User::create([
            'name' => 'Invitado Test',
            'email' => 'invitado_prod@test.com',
            'password' => Hash::make('Invit123*'),
            'role' => 'invitado',
            'active' => true,
        ]);
    }

    public function test_roles_and_permissions_matrix(): void
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->tecnico->isTecnico());
        $this->assertTrue($this->invitado->isInvitado());

        // Gates
        $this->assertTrue(Gate::forUser($this->admin)->allows('promote-admin'));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('promote-admin'));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('promote-admin'));
    }

    public function test_atomic_stock_flow_and_overdraft_protection(): void
    {
        $categoria = CategoriaStock::create(['nombre' => 'General', 'tipo' => 'categoria']);
        $stock = Stock::create([
            'codigo' => 'STK-001',
            'producto' => 'Batería Laptop 6 Celdas',
            'categoria_id' => $categoria->id,
            'cantidad' => 5,
            'stock_minimo' => 1,
            'precio_compra' => 100000,
            'precio_venta' => 150000,
            'activo' => true,
        ]);

        $stockService = app(StockService::class);
        
        // Entrada
        $stockService->entrada($stock, 10);
        $this->assertEquals(15, $stock->fresh()->cantidad);

        // Salida
        $stockService->salida($stock, 4);
        $this->assertEquals(11, $stock->fresh()->cantidad);

        // Protección de sobregiro
        $this->expectException(\DomainException::class);
        $stockService->salida($stock, 999);
    }

    public function test_financial_partial_payment_and_cancellation(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'identificacion' => 'CC-123456789',
            'telefono' => '3100000000',
            'movil' => '3100000000',
            'email' => 'juan@test.com',
            'direccion' => 'Calle 10',
            'activo' => true,
        ]);

        $concepto = ConceptoCaja::create(['nombre' => 'Venta']);

        $factura = Factura::create([
            'numero_factura' => 'VT-TEST-001',
            'tipo_movimiento' => 'venta',
            'estado' => 'pendiente_pago',
            'facturable_id' => $cliente->id,
            'facturable_type' => Cliente::class,
            'total_documento' => 300000,
            'total_pagado' => 100000,
            'fecha' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $movPadre = MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 100000,
            'monto_total' => 300000,
            'persona' => $cliente->nombre,
            'concepto_id' => $concepto->id,
            'descripcion' => "Cobro venta #{$factura->numero_factura}",
            'fecha' => now()->toDateString(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $this->assertEquals(200000, $factura->saldo_pendiente);

        // Registrar Abono
        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'consignacion',
            'monto' => 150000,
            'monto_total' => 0,
            'persona' => $cliente->nombre,
            'concepto_id' => $concepto->id,
            'descripcion' => "Abono a #{$factura->numero_factura}",
            'fecha' => now()->toDateString(),
            'estado' => 'activo',
            'anulado' => false,
            'parent_id' => $movPadre->id,
            'user_id' => $this->admin->id,
        ]);

        $factura->recalcularPagos();
        $this->assertEquals(250000, $factura->fresh()->total_pagado);
        $this->assertEquals(50000, $factura->fresh()->saldo_pendiente);

        // Anular
        $movPadre->update(['estado' => 'anulado', 'anulado' => true]);
        $movPadre->childPayments()->update(['estado' => 'anulado', 'anulado' => true]);
        $factura->update(['estado' => 'anulada', 'total_pagado' => 0]);

        $this->assertTrue($movPadre->fresh()->anulado);
        $this->assertEquals(1, $movPadre->childPayments()->where('anulado', true)->count());
    }
}
