<?php

namespace Tests\Unit;

use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\AnulacionService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AnulacionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected ConceptoCaja $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'active' => true,
        ]);

        $this->tecnico = User::create([
            'name' => 'Tecnico Test',
            'email' => 'tecnico_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'tecnico',
            'active' => true,
        ]);

        $this->concepto = ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);
    }

    public function test_admin_password_valida_returns_true_for_correct_admin_password(): void
    {
        $service = new AnulacionService;
        $this->assertTrue($service->adminPasswordValida('password'));
    }

    public function test_admin_password_valida_returns_false_for_incorrect_password(): void
    {
        $service = new AnulacionService;
        $this->assertFalse($service->adminPasswordValida('wrong_password'));
    }

    public function test_password_valida_returns_true_for_current_user_password(): void
    {
        Auth::login($this->admin);
        $service = new AnulacionService;
        $this->assertTrue($service->passwordValida('password'));
    }

    public function test_password_valida_returns_true_for_admin_password_when_not_admin(): void
    {
        Auth::login($this->tecnico);
        $service = new AnulacionService;
        $this->assertTrue($service->passwordValida('password'));
    }

    public function test_autorizar_operacion_sensible_allows_admin_without_password(): void
    {
        Auth::login($this->admin);
        $request = new Request;
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertNull($result);
    }

    public function test_autorizar_operacion_sensible_denies_invitado(): void
    {
        $invitado = User::create([
            'name' => 'Invitado',
            'email' => 'invitado@test.com',
            'password' => bcrypt('password'),
            'role' => 'invitado',
            'active' => true,
        ]);
        Auth::login($invitado);
        $request = new Request;
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertEquals('No tienes permisos para realizar esta acción.', $result);
    }

    public function test_autorizar_operacion_sensible_requires_admin_password_for_tecnico(): void
    {
        Auth::login($this->tecnico);
        $request = new Request;
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertEquals('Se requiere la contraseña de un administrador para autorizar esta operación.', $result);
    }

    public function test_autorizar_operacion_sensile_rejects_wrong_admin_password_for_tecnico(): void
    {
        Auth::login($this->tecnico);
        $request = new Request(['admin_password' => 'wrong_password']);
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertEquals('Contraseña de administrador incorrecta.', $result);
    }

    public function test_autorizar_operacion_sensile_accepts_correct_admin_password_for_tecnico(): void
    {
        Auth::login($this->tecnico);
        $request = new Request(['admin_password' => 'password']);
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertNull($result);
    }

    public function test_autorizar_operacion_sensile_accepts_password_confirm_field(): void
    {
        Auth::login($this->tecnico);
        $request = new Request(['password_confirm' => 'password']);
        $service = new AnulacionService;

        $result = $service->autorizarOperacionSensible($request);
        $this->assertNull($result);
    }

    public function test_revertir_stock_y_abonos_anula_stock_and_caja(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-ANUL-1',
            'producto' => 'Para Anular',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'active' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez',
            'identificacion' => '12345678', 'movil' => '3001234567',
        ]);

        $equipo = Equipo::create([
            'nombre' => 'Laptop', 'marca' => 'HP', 'modelo' => 'Pavilion',
            'serie' => 'SN-ANUL-1', 'cliente_id' => $cliente->id, 'user_id' => $this->admin->id,
        ]);

        $tecnicoM = Tecnico::create([
            'nombre' => 'Tecnico', 'identificacion' => '11111111',
            'especialidad' => 'General', 'movil' => '3001111111',
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-ANUL-1',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test anular',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $mantenimiento->stocks()->attach($stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        // Simulate stock decrement that happens in HandlesStockAttach::attachStock
        app(StockService::class)->salida($stock, 2);
        $stock->refresh();
        $this->assertEquals(8, $stock->cantidad);

        $abono = Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
        ]);

        MovimientoCaja::create([
            'abono_id' => $abono->id,
            'persona' => 'Juan Pérez',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'Abono Orden ORD-ANUL-1',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $service = new AnulacionService;
        $service->revertirStockYAbonos($mantenimiento, true, 'Abono Mantenimiento', ['Orden']);

        $stock->refresh();
        $this->assertEquals(10, $stock->cantidad);

        $movCaja = MovimientoCaja::where('abono_id', $abono->id)->first();
        $this->assertNotNull($movCaja);
        $movCaja->refresh();
        $this->assertTrue($movCaja->anulado);
        $this->assertEquals('anulado', $movCaja->estado);
    }

    public function test_revertir_stock_y_abonos_reactiva_stock_and_caja(): void
    {
        $stock = Stock::create([
            'codigo' => 'STK-REACT-1',
            'producto' => 'Para Reactivar',
            'categoria' => 'Test',
            'cantidad' => 10,
            'precio_compra' => 80000,
            'utilidad' => 50,
            'active' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez',
            'identificacion' => '12345679', 'movil' => '3001234568',
        ]);

        $equipo = Equipo::create([
            'nombre' => 'Laptop', 'marca' => 'HP', 'modelo' => 'Pavilion',
            'serie' => 'SN-REACT-1', 'cliente_id' => $cliente->id, 'user_id' => $this->admin->id,
        ]);

        $tecnicoM = Tecnico::create([
            'nombre' => 'Tecnico', 'identificacion' => '22222222',
            'especialidad' => 'General', 'movil' => '3002222222',
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-REACT-1',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test reactivar',
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'costo' => 150000,
            'estado' => 'pendiente',
            'anulado' => true,
        ]);

        $mantenimiento->stocks()->attach($stock->id, ['cantidad' => 2, 'precio_unitario' => 120000]);
        // Simulate stock decrement that happens in HandlesStockAttach::attachStock
        app(StockService::class)->salida($stock, 2);
        // Simulate stock increment that happens on anulación
        app(StockService::class)->entrada($stock, 2);
        $stock->refresh();
        $this->assertEquals(10, $stock->cantidad);

        $abono = Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
        ]);

        MovimientoCaja::create([
            'abono_id' => $abono->id,
            'persona' => 'Juan Pérez',
            'fecha' => now()->toDateString(),
            'concepto_id' => $this->concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'descripcion' => 'Abono Orden ORD-REACT-1',
            'estado' => 'anulado',
            'anulado' => true,
            'user_id' => $this->admin->id,
        ]);

        $service = new AnulacionService;
        $service->revertirStockYAbonos($mantenimiento, false, 'Abono Mantenimiento', ['Orden']);

        $stock->refresh();
        $this->assertEquals(8, $stock->cantidad);

        $movCaja = MovimientoCaja::where('abono_id', $abono->id)->first();
        $this->assertNotNull($movCaja);
        $movCaja->refresh();
        $this->assertFalse($movCaja->anulado);
        $this->assertEquals('activo', $movCaja->estado);
    }

    public function test_revertir_stock_y_abonos_throws_on_closed_caja_date(): void
    {
        $service = new AnulacionService;
        $this->expectException(\DomainException::class);

        $fechaCerrada = now()->subDays(2)->toDateString();
        CierreCaja::create([
            'fecha' => $fechaCerrada,
            'total_ingresos' => 100000, 'total_egresos' => 0,
            'efectivo' => 100000, 'consignacion' => 0,
            'saldo_final' => 100000, 'num_movimientos' => 1,
            'bloqueado' => true, 'user_id' => $this->admin->id,
        ]);

        $stock = Stock::create([
            'codigo' => 'STK-CLOSED-1', 'producto' => 'Test',
            'categoria' => 'Test', 'cantidad' => 10,
            'precio_compra' => 80000, 'utilidad' => 50, 'active' => true,
        ]);

        $cliente = Cliente::create([
            'nombres' => 'Juan', 'apellidos' => 'Pérez',
            'identificacion' => '12345680', 'movil' => '3001234569',
        ]);

        $equipo = Equipo::create([
            'nombre' => 'Laptop', 'marca' => 'HP', 'modelo' => 'Pavilion',
            'serie' => 'SN-CLOSED-1', 'cliente_id' => $cliente->id, 'user_id' => $this->admin->id,
        ]);

        $tecnicoM = Tecnico::create([
            'nombre' => 'Tecnico', 'identificacion' => '33333333',
            'especialidad' => 'General', 'movil' => '3003333333',
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-CLOSED-1',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnicoM->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Test closed',
            'tipo' => 'correctivo', 'reparacion' => 'hardware',
            'costo' => 150000, 'estado' => 'pendiente', 'anulado' => false,
        ]);

        $mantenimiento->stocks()->attach($stock->id, ['cantidad' => 1, 'precio_unitario' => 120000]);

        $abono = Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'monto' => 50000,
            'fecha' => $fechaCerrada,
            'tipo_pago' => 'efectivo',
            'user_id' => $this->admin->id,
        ]);

        $service->revertirStockYAbonos($mantenimiento, true, 'Abono Mantenimiento', ['Orden']);
    }
}
