<?php

namespace Tests\Unit;

use App\Models\CierreCaja;
use App\Models\User;
use App\Services\CierreCajaGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreCajaGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_fecha_esta_cerrada_returns_false_when_empty(): void
    {
        $result = CierreCajaGuard::fechaEstaCerrada(null);
        $this->assertFalse($result);

        $result = CierreCajaGuard::fechaEstaCerrada('');
        $this->assertFalse($result);
    }

    public function test_fecha_esta_cerrada_returns_false_when_no_cierre_exists(): void
    {
        $result = CierreCajaGuard::fechaEstaCerrada('2026-01-15');
        $this->assertFalse($result);
    }

    public function test_fecha_esta_cerrada_returns_true_when_cierre_exists(): void
    {
        CierreCaja::create([
            'fecha' => '2026-01-15',
            'total_ingresos' => 100000,
            'total_egresos' => 20000,
            'efectivo' => 80000,
            'consignacion' => 0,
            'saldo_final' => 80000,
            'num_movimientos' => 5,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $result = CierreCajaGuard::fechaEstaCerrada('2026-01-15');
        $this->assertTrue($result);
    }

    public function test_fecha_esta_cerrada_uses_date_comparison(): void
    {
        CierreCaja::create([
            'fecha' => '2026-01-15 10:30:00',
            'total_ingresos' => 100000,
            'total_egresos' => 20000,
            'efectivo' => 80000,
            'consignacion' => 0,
            'saldo_final' => 80000,
            'num_movimientos' => 5,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        // Should still match with just the date
        $result = CierreCajaGuard::fechaEstaCerrada('2026-01-15');
        $this->assertTrue($result);
    }

    public function test_asegurar_abierta_throws_when_closed(): void
    {
        CierreCaja::create([
            'fecha' => '2026-01-15',
            'total_ingresos' => 100000,
            'total_egresos' => 20000,
            'efectivo' => 80000,
            'consignacion' => 0,
            'saldo_final' => 80000,
            'num_movimientos' => 5,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No se puede registrar movimiento de caja con fecha 2026-01-15');

        CierreCajaGuard::asegurarAbierta('2026-01-15', 'movimiento de caja');
    }

    public function test_asegurar_abierta_passes_when_open(): void
    {
        // No cierre exists
        CierreCajaGuard::asegurarAbierta('2026-01-15', 'venta');
        $this->assertTrue(true); // If no exception thrown, test passes
    }

    public function test_asegurar_abierta_custom_context_message(): void
    {
        CierreCaja::create([
            'fecha' => '2026-01-15',
            'total_ingresos' => 100000,
            'total_egresos' => 20000,
            'efectivo' => 80000,
            'consignacion' => 0,
            'saldo_final' => 80000,
            'num_movimientos' => 5,
            'bloqueado' => true,
            'user_id' => $this->admin->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('venta de inventario');

        CierreCajaGuard::asegurarAbierta('2026-01-15', 'venta de inventario');
    }
}
