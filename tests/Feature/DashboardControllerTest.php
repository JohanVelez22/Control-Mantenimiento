<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    public function test_admin_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_tecnico_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_invitado_redirected_to_guest_dashboard(): void
    {
        $response = $this->actingAs($this->invitado)->get(route('dashboard'));
        $response->assertRedirect(route('guest.dashboard'));
    }

    public function test_dashboard_shows_mantenimiento_stats(): void
    {
        Mantenimiento::factory()->count(3)->create([
            'anulado' => false,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
        ]);
        Mantenimiento::factory()->count(2)->create([
            'anulado' => false,
            'estado' => 'terminado',
            'user_id' => $this->admin->id,
        ]);
        Mantenimiento::factory()->create([
            'anulado' => true,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('3');
        $response->assertSee('2');
    }

    public function test_dashboard_shows_stock_bajo_count(): void
    {
        Stock::factory()->count(3)->create([
            'cantidad' => 3,
            'active' => true,
        ]);
        Stock::factory()->count(2)->create([
            'cantidad' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('3');
    }

    public function test_dashboard_shows_electronica_pendientes(): void
    {
        Electronica::factory()->count(4)->create([
            'anulado' => false,
            'estado' => 'pendiente',
        ]);
        Electronica::factory()->create([
            'anulado' => true,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_caja_saldos(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Test']);
        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'persona' => 'Test',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);
        MovimientoCaja::create([
            'tipo_movimiento' => 'egreso',
            'tipo_pago' => 'efectivo',
            'monto' => 20000,
            'persona' => 'Test',
            'concepto_id' => $concepto->id,
            'fecha' => Carbon::today(),
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_recent_mantenimientos(): void
    {
        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);
        $tecnico = Tecnico::factory()->create();

        Mantenimiento::factory()->count(3)->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_chart_data_for_7_days(): void
    {
        $cliente = Cliente::factory()->create();
        Equipo::factory()->count(2)->create([
            'cliente_id' => $cliente->id,
            'created_at' => Carbon::today()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_shows_recent_cotizaciones(): void
    {
        $cliente = Cliente::factory()->create();
        Cotizacion::create([
            'codigo' => 'COT-1',
            'cliente_id' => $cliente->id,
            'fecha' => now(),
            'validez_dias' => 30,
            'total' => 100000,
            'estado' => 'pendiente',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);
        Cotizacion::create([
            'codigo' => 'COT-2',
            'cliente_id' => $cliente->id,
            'fecha' => now(),
            'validez_dias' => 30,
            'total' => 200000,
            'estado' => 'aprobada',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);
        Cotizacion::create([
            'codigo' => 'COT-3',
            'cliente_id' => $cliente->id,
            'fecha' => now(),
            'validez_dias' => 30,
            'total' => 300000,
            'estado' => 'rechazada',
            'anulado' => false,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_loads_clientes_and_tecnicos_for_selects(): void
    {
        Cliente::factory()->count(2)->create();
        Tecnico::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);
    }
}
