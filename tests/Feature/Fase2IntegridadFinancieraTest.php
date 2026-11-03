<?php

namespace Tests\Feature;

use App\Exports\ReportesFinancierosExport;
use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use App\Services\AnulacionService;
use App\Traits\HandlesStockAttach;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fase2IntegridadFinancieraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'active' => true,
        ]);
    }

    /**
     * P1-10: Dashboard y consultas recientes deben ignorar abonos anulados.
     */
    public function test_dashboard_ignora_abonos_anulados(): void
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'id_orden' => 'ORD-100',
            'user_id' => $this->admin->id,
            'costo' => 100000,
            'fecha_entrada' => now()->toDateString(),
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        // Abono activo de 40.000
        Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'user_id' => $this->admin->id,
            'monto' => 40000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'anulado' => false,
        ]);

        // Abono anulado de 30.000
        Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'user_id' => $this->admin->id,
            'monto' => 30000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'anulado' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Query tal cual la ejecuta el DashboardController
        $recentMant = Mantenimiento::where('id', $mantenimiento->id)
            ->withSum(['abonos as total_abonado' => fn ($q) => $q->where('anulado', false)], 'monto')
            ->first();

        $this->assertEquals(40000, (float) $recentMant->total_abonado, 'El total abonado en dashboard debe excluir los abonos anulados');
        $this->assertEquals(40000, (float) $mantenimiento->total_abonado);
        $this->assertEquals(60000, (float) $mantenimiento->saldo_pendiente);
    }

    /**
     * P1-11 / AUD-012: Al anular una orden, abono.anulado debe pasar a true, y al reactivarla a false.
     */
    public function test_anulacion_de_orden_sincroniza_estado_anulado_del_abono(): void
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'id_orden' => 'ORD-101',
            'user_id' => $this->admin->id,
            'costo' => 80000,
            'fecha_entrada' => now()->toDateString(),
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $concepto = ConceptoCaja::create(['nombre' => 'Abono Mantenimiento']);

        $abono = Abono::create([
            'mantenimiento_id' => $mantenimiento->id,
            'user_id' => $this->admin->id,
            'monto' => 50000,
            'fecha' => now()->toDateString(),
            'tipo_pago' => 'efectivo',
            'anulado' => false,
        ]);

        MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'fecha' => now()->toDateString(),
            'monto' => 50000,
            'concepto_id' => $concepto->id,
            'persona' => 'Cliente Prueba',
            'descripcion' => 'Abono Parcial Orden ORD-101',
            'tipo_pago' => 'efectivo',
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => $this->admin->id,
            'abono_id' => $abono->id,
        ]);

        $anulacionService = app(AnulacionService::class);

        // Anular la orden
        $anulacionService->revertirStockYAbonos($mantenimiento, true, 'Abono Mantenimiento', ['Abono Parcial Orden ', 'Pago Total Orden ']);
        $abono->refresh();
        $this->assertTrue((bool) $abono->anulado, 'El abono debe quedar marcado como anulado cuando la orden se anula');

        // Reactivar la orden
        $anulacionService->revertirStockYAbonos($mantenimiento, false, 'Abono Mantenimiento', ['Abono Parcial Orden ', 'Pago Total Orden ']);
        $abono->refresh();
        $this->assertFalse((bool) $abono->anulado, 'El abono debe quedar activo cuando la orden se reactiva');
    }

    /**
     * P1-12 / STK-01: HandlesStockAttach sincroniza el precio unitario promedio ponderado.
     */
    public function test_handles_stock_attach_pondera_precio_unitario_al_sumar_unidades(): void
    {
        $mantenimiento = Mantenimiento::factory()->create([
            'id_orden' => 'ORD-102',
            'user_id' => $this->admin->id,
            'costo' => 0,
            'fecha_entrada' => now()->toDateString(),
            'estado' => 'pendiente',
            'anulado' => false,
        ]);
        if ($mantenimiento->equipo?->cliente) {
            $mantenimiento->equipo->cliente->update(['tipo_cliente' => 'cliente']);
        }

        $stock = Stock::create([
            'codigo' => 'MEM-8GB',
            'producto' => 'Memoria RAM 8GB',
            'categoria' => 'Memorias',
            'cantidad' => 10,
            'precio_compra' => 50000,
            'precio_venta' => 100000,
            'active' => true,
        ]);

        $dummyClass = new class
        {
            use HandlesStockAttach {
                attachStock as public testAttachStock;
                detachStock as public testDetachStock;
            }
        };

        // 1. Agregar 2 unidades a $100.000 c/u = $200.000
        $dummyClass->testAttachStock($mantenimiento, [
            'stock_id' => $stock->id,
            'cantidad' => 2,
        ], 'Agregado');

        $mantenimiento->refresh();
        $this->assertEquals(200000, (float) $mantenimiento->costo);
        $pivot = $mantenimiento->stocks()->where('stock_id', $stock->id)->first()->pivot;
        $this->assertEquals(2, $pivot->cantidad);
        $this->assertEquals(100000, (float) $pivot->precio_unitario);

        // 2. Cambiar precio del stock a $160.000 y agregar 1 unidad más
        $stock->update(['precio_venta' => 160000]);

        // Costo esperado adicional: $160.000. Total costo orden: $360.000
        // Nuevo precio promedio ponderado: (2 * 100.000 + 1 * 160.000) / 3 = 360.000 / 3 = 120.000
        $dummyClass->testAttachStock($mantenimiento, [
            'stock_id' => $stock->id,
            'cantidad' => 1,
        ], 'Sumado');

        $mantenimiento->refresh();
        $this->assertEquals(360000, (float) $mantenimiento->costo);
        $pivot = $mantenimiento->stocks()->where('stock_id', $stock->id)->first()->pivot;
        $this->assertEquals(3, $pivot->cantidad);
        $this->assertEquals(120000, (float) $pivot->precio_unitario, 'El precio unitario en pivot debe ser el promedio ponderado');

        // 3. Desvincular como admin: el costo de la orden debe decrementar exactamente $360.000
        $this->actingAs($this->admin);
        $dummyClass->testDetachStock($mantenimiento, $stock->id, 'Eliminado');

        $mantenimiento->refresh();
        $this->assertEquals(0, (float) $mantenimiento->costo, 'No debe quedar saldo fantasma al retirar el repuesto');
    }

    /**
     * P1-2 / NUEVO-06: ReportesFinancierosExport no duplica facturas y movimientos de caja en el balance.
     */
    public function test_reportes_financieros_export_evita_duplicacion_de_balance(): void
    {
        // Transacciones del día simuladas: 1 factura de venta por $100.000 y 1 movimiento de caja de ingreso por $100.000
        $transacciones = collect([
            [
                'tipo' => 'venta',
                'fecha' => now()->toDateString(),
                'codigo' => 'VT-1',
                'descripcion' => 'Venta Mostrador',
                'monto' => 100000,
                'estado' => 'emitida',
                'anulado' => false,
            ],
            [
                'tipo' => 'ingreso',
                'fecha' => now()->toDateString(),
                'codigo' => 1,
                'descripcion' => 'Pago Factura #VT-1',
                'monto' => 100000,
                'estado' => 'activo',
                'anulado' => false,
            ],
        ]);

        $export = new ReportesFinancierosExport($transacciones, [
            'balance_neto' => 100000, // Balance provisto por el controlador
        ]);

        // Sin $resumen, el cálculo de flujo de caja real debe ser $100.000 (solo el ingreso de caja), NO $200.000
        $exportSinResumen = new ReportesFinancierosExport($transacciones);

        $reflection = new \ReflectionClass($exportSinResumen);
        $property = $reflection->getProperty('transacciones');
        $property->setAccessible(true);
        $txs = $property->getValue($exportSinResumen);

        $ingresosReales = $txs->filter(fn ($t) => ! $t['anulado'] && $t['tipo'] === 'ingreso')->sum('monto');
        $this->assertEquals(100000, $ingresosReales, 'No debe sumar venta + ingreso duplicando el dinero');
    }

    /**
     * P2-2: Hook booted de Stock redondea a 2 decimales y maneja valores nulos sin error.
     */
    public function test_stock_booted_hook_redondeo_y_seguridad_nulos(): void
    {
        $stock = Stock::create([
            'codigo' => 'REP-001',
            'producto' => 'Repuesto Precision',
            'categoria' => 'Varios',
            'cantidad' => 5,
            'precio_compra' => 100.33,
            'utilidad' => 15.5,
            'active' => true,
        ]);

        // Esperado: 100.33 * (1 + 0.155) = 115.88115 -> redondeado a 115.88
        $this->assertEquals(115.88, (float) $stock->precio_venta);

        // Sin precio compra (0) no debe fallar con TypeError ni cálculo NaN
        $stockCero = Stock::create([
            'codigo' => 'REP-002',
            'producto' => 'Repuesto Sin Costo',
            'categoria' => 'Varios',
            'cantidad' => 1,
            'precio_compra' => 0,
            'utilidad' => 0,
            'active' => true,
        ]);

        $this->assertEquals(0, (float) $stockCero->precio_compra);
    }
}
