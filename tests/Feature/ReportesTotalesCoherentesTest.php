<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * X-04 a X-09 y X-13: los totales de pantalla, PDF y Excel se calculan igual, sobre
 * toda la consulta filtrada y sin las anuladas; los documentos muestran los datos
 * reales del cliente.
 */
class ReportesTotalesCoherentesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    public function test_x05_x06_reporte_de_mantenimientos_suma_todo_el_filtro_sin_anuladas(): void
    {
        Mantenimiento::factory()->count(12)->create(['costo' => 10000, 'anulado' => false, 'fecha_entrada' => now()->toDateString()]);
        Mantenimiento::factory()->create(['costo' => 99000, 'anulado' => true, 'fecha_entrada' => now()->toDateString()]);

        $resp = $this->actingAs($this->admin)->get(route('mantenimientos.reportes'))->assertOk();

        $totales = $resp->viewData('totales');
        $this->assertSame(13, $totales['cantidad']);
        $this->assertEquals(120000, $totales['costo']);
        $this->assertSame(1, $totales['anuladas']);
        $resp->assertSee('120.000');
    }

    public function test_x05_reporte_de_electronica_excluye_anuladas_del_total(): void
    {
        Electronica::factory()->count(11)->create(['costo' => 5000, 'anulado' => false, 'fecha_entrada' => now()->toDateString()]);
        Electronica::factory()->create(['costo' => 70000, 'anulado' => true, 'fecha_entrada' => now()->toDateString()]);

        $totales = $this->actingAs($this->admin)->get(route('electronicas.reportes'))->assertOk()->viewData('totales');

        $this->assertSame(12, $totales['cantidad']);
        $this->assertEquals(55000, $totales['costo']);
    }

    public function test_x04_valor_de_inventario_es_cantidad_por_precio_en_pantalla_y_pdf(): void
    {
        foreach (range(1, 22) as $i) {
            Stock::create([
                'codigo' => "S-{$i}", 'producto' => "Prod {$i}", 'categoria' => 'C', 'subcategoria' => 'S',
                'cantidad' => 3, 'precio_compra' => 1000, 'utilidad' => 50, 'precio_venta' => 1500, 'active' => true,
            ]);
        }

        $totales = $this->actingAs($this->admin)->get(route('stocks.reportes'))->assertOk()->viewData('totales');

        // 22 productos × 3 unidades × $1.000 (antes: suma de precios unitarios de la página)
        $this->assertSame(22, $totales['registros']);
        $this->assertSame(66, $totales['cantidad']);
        $this->assertEquals(66000, $totales['valor_compra']);
        $this->assertEquals(99000, $totales['valor_venta']);

        $html = view('stocks.pdf_reportes', ['stocks' => Stock::all(), 'totales' => $totales])->render();
        $this->assertStringContainsString('66.000', $html);
    }

    public function test_x05_operaciones_suma_todos_los_ingresos_del_periodo(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Servicio']);
        foreach (range(1, 14) as $i) {
            MovimientoCaja::create([
                'persona' => "P{$i}", 'fecha' => now()->toDateString(), 'concepto_id' => $concepto->id,
                'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000,
                'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
            ]);
        }

        $resp = $this->actingAs($this->admin)
            ->get(route('reportes.financiero.operaciones', ['tipo' => 'solo_ingresos']))->assertOk();

        $this->assertSame(['cantidad' => 14, 'monto' => 14000.0], $resp->viewData('totales'));
        $this->assertSame(10, $resp->viewData('registros')->count());
    }

    public function test_x09_excel_escribe_los_ceros(): void
    {
        $this->assertTrue(config('excel.exports.strict_null_comparison'));
        // Las demás opciones del paquete se conservan.
        $this->assertSame(1000, config('excel.exports.chunk_size'));
        $this->assertSame(',', config('excel.exports.csv.delimiter'));
    }

    public function test_x13_factura_de_inventario_muestra_identificacion_y_movil_del_cliente(): void
    {
        $cliente = Cliente::factory()->create(['identificacion' => '1020304050', 'movil' => '3001234567']);
        $factura = new Factura([
            'numero_factura' => 'VT-1', 'tipo_movimiento' => 'venta', 'fecha' => now(),
            'total_documento' => 1000, 'estado' => 'emitida',
        ]);
        $factura->setRelation('facturable', $cliente);
        $factura->setRelation('items', collect());
        $factura->setRelation('user', $this->admin);

        foreach (['inventario.facturas.print', 'inventario.facturas.print_pos'] as $vista) {
            $html = view($vista, ['factura' => $factura, 'abonos' => collect(), 'empresa' => null])->render();
            $this->assertStringContainsString('1020304050', $html, $vista);
            $this->assertStringContainsString('3001234567', $html, $vista);
        }
    }
}
