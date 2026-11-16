<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Services\CierreCajaGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * D-04 / D-23: filtros por fecha como rangos sobre la columna y tope de exportación.
 */
class RendimientoConsultasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    private function movimiento(string $fecha): void
    {
        MovimientoCaja::create([
            'persona' => 'X', 'fecha' => $fecha, 'concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000,
            'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
        ]);
    }

    public function test_filtros_de_dia_y_mes_incluyen_los_bordes_y_no_usan_funciones_de_fecha(): void
    {
        foreach (['2026-10-31', '2026-11-01', '2026-11-15', '2026-11-30', '2026-12-01'] as $fecha) {
            $this->movimiento($fecha);
        }

        DB::enableQueryLog();
        $this->assertSame(3, MovimientoCaja::whereMes('fecha', 11, 2026)->count());
        $this->assertSame(1, MovimientoCaja::whereDia('fecha', '2026-11-15')->count());
        $this->assertSame(4, MovimientoCaja::whereDesdeDia('fecha', '2026-11-01')->count());
        $this->assertSame(4, MovimientoCaja::whereHastaDia('fecha', '2026-11-30')->count());
        $sql = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        $this->assertDoesNotMatchRegularExpression('/strftime|date\(|month\(|year\(/i', $sql);
    }

    public function test_guardian_de_cierre_sigue_detectando_el_dia_cerrado(): void
    {
        DB::table('cierre_cajas')->insert([
            'fecha' => '2026-11-10', 'total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0, 'diferencia' => 0,
            'consignacion' => 0, 'saldo_final' => 0, 'num_movimientos' => 0, 'bloqueado' => true, 'user_id' => $this->admin->id,
        ]);

        $this->assertTrue(CierreCajaGuard::fechaEstaCerrada('2026-11-10'));
        $this->assertTrue(CierreCajaGuard::fechaEstaCerrada('2026-11-09'));
        $this->assertFalse(CierreCajaGuard::fechaEstaCerrada('2026-11-11'));
    }

    public function test_exportacion_por_encima_del_tope_pide_acotar(): void
    {
        $controlador = new class extends Controller
        {
            public function probar(int $filas, ?string $formato)
            {
                return $this->limiteExportacion($filas, $formato);
            }
        };

        $this->assertNull($controlador->probar(100, 'pdf'));
        $this->assertNull($controlador->probar(50000, null));
        $this->assertNotNull($controlador->probar(3001, 'pdf'));
    }

    public function test_mes_invalido_en_el_informe_mensual_se_rechaza(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.index', ['mes' => 13]))->assertSessionHasErrors('mes');
    }
}
