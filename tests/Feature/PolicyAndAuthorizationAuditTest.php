<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyAndAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tecnico;
    private User $invitado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);

        $this->tecnico = User::factory()->create([
            'role' => 'tecnico',
            'active' => true,
        ]);

        $this->invitado = User::factory()->create([
            'role' => 'invitado',
            'active' => true,
        ]);
    }

    public function test_gates_evaluation_for_roles(): void
    {
        // manage-caja
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-caja'));
        $this->assertTrue(Gate::forUser($this->tecnico)->allows('manage-caja'));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('manage-caja'));

        // anular-operacion
        $this->assertTrue(Gate::forUser($this->admin)->allows('anular-operacion'));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('anular-operacion'));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('anular-operacion'));

        // manage-users
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-users'));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('manage-users'));

        // manage-backups
        $this->assertTrue(Gate::forUser($this->admin)->allows('manage-backups'));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('manage-backups'));
    }

    public function test_factura_policy_authorization(): void
    {
        $factura = new Factura();

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Factura::class));
        $this->assertTrue(Gate::forUser($this->tecnico)->allows('viewAny', Factura::class));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('viewAny', Factura::class));

        $this->assertTrue(Gate::forUser($this->admin)->allows('anular', $factura));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('anular', $factura));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('anular', $factura));
    }

    public function test_cierre_caja_policy_authorization(): void
    {
        $cierre = new CierreCaja();

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', CierreCaja::class));
        $this->assertTrue(Gate::forUser($this->tecnico)->allows('viewAny', CierreCaja::class));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('viewAny', CierreCaja::class));

        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $cierre));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('delete', $cierre));
    }

    public function test_stock_policy_authorization(): void
    {
        $stock = new Stock();

        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', Stock::class));
        $this->assertTrue(Gate::forUser($this->tecnico)->allows('viewAny', Stock::class));
        $this->assertFalse(Gate::forUser($this->invitado)->allows('viewAny', Stock::class));

        $this->assertTrue(Gate::forUser($this->admin)->allows('revertirBaja', $stock));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('revertirBaja', $stock));
    }

    public function test_user_policy_authorization(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('viewAny', User::class));

        // Self-update
        $this->assertTrue(Gate::forUser($this->tecnico)->allows('update', $this->tecnico));
        $this->assertFalse(Gate::forUser($this->tecnico)->allows('update', $this->admin));

        // Delete (cannot delete self)
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $this->tecnico));
        $this->assertFalse(Gate::forUser($this->admin)->allows('delete', $this->admin));
    }
}

