<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

/**
 * Lo que el usuario ve en pantalla, lo que descarga en Excel y lo que imprime en PDF deben
 * mostrar las mismas cifras. Se genera un día con movimientos conocidos y se comparan:
 *   - pantalla (viewData 'resumen' y 'movimientos')
 *   - Excel real descargado y leído con PhpSpreadsheet (filas, columna Costo y pie "Balance Neto")
 *   - PDF: datos que recibe la plantilla pdf_diario y HTML renderizado con esos datos
 *
 * Cifras esperadas (cálculo manual):
 *   ingreso efectivo 120.000 + venta (cobro efectivo) 30.000 = 150.000 de ingresos
 *   egreso consignación 20.000 → balance neto 130.000
 *   ingreso 500.000 anulado → no suma al balance pero sí aparece listado como anulado
 */
class ReporteDiarioExportCoincideTest extends TestCase
{
    use RefreshDatabase;

    private const DIA = '2026-04-15';

    private function sembrarDia(): void
    {
        $this->travelTo(now()->setDate(2026, 4, 15)->setTime(9, 30));
        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($admin);

        $cliente = Cliente::factory()->create();
        $stock = Stock::create([
            'codigo' => 'CAB-01', 'producto' => 'Cable HDMI', 'categoria' => 'Accesorios', 'cantidad' => 10,
            'precio_compra' => 10000, 'utilidad' => 150, 'precio_venta' => 25000, 'active' => true,
        ]);

        $mov = fn (array $d) => $this->post(route('caja.store'), $d + ['fecha' => self::DIA])->assertSessionHasNoErrors();
        $mov(['persona' => 'Cliente Mostrador', 'nuevo_concepto' => 'Servicio', 'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 120000]);
        $mov(['empresa' => 'Internet SAS', 'nuevo_concepto' => 'Servicios públicos', 'tipo_movimiento' => 'egreso', 'tipo_pago' => 'consignacion', 'monto' => 20000]);
        $mov(['persona' => 'Error', 'nuevo_concepto' => 'Varios', 'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 500000]);
        $this->post(route('caja.anular', MovimientoCaja::where('monto', 500000)->first()), ['accion' => 'anular'])->assertSessionHas('success');

        $this->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$cliente->id, 'fecha' => self::DIA, 'total_pagado' => 30000, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => 25000]],
        ])->assertSessionMissing('error');
    }

    public function test_pantalla_excel_y_pdf_muestran_los_mismos_totales(): void
    {
        $this->sembrarDia();

        // ── Pantalla
        $pantalla = $this->get(route('reportes.financiero.diario', ['fecha' => self::DIA]))->assertOk();
        $resumen = $pantalla->viewData('resumen');
        $movimientos = $pantalla->viewData('movimientos');

        $this->assertEqualsWithDelta(150000, $resumen['total_ingresos'], 0.001);
        $this->assertEqualsWithDelta(20000, $resumen['total_egresos'], 0.001);
        $this->assertEqualsWithDelta(130000, $resumen['balance_neto'], 0.001);
        $this->assertEqualsWithDelta(50000, $resumen['total_ventas'], 0.001);
        $this->assertEqualsWithDelta(20000, $resumen['total_por_cobrar'], 0.001);
        $this->assertEqualsWithDelta(500000, $resumen['monto_anulados'], 0.001);
        // Lo que se ve en pantalla incluye el valor formateado del balance
        $pantalla->assertSee('130.000');

        // ── Excel real
        $excel = $this->get(route('reportes.financiero.diario', ['fecha' => self::DIA, 'export' => 'excel']))->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $excel->baseResponse);
        $hoja = IOFactory::load($excel->baseResponse->getFile()->getPathname())->getActiveSheet();
        $filas = $hoja->toArray(null, true, false, false);

        // Encabezados en la fila 4 (índice 3); los datos empiezan en la fila 5
        $this->assertSame('Costo', $filas[3][4]);
        $datos = array_values(array_filter(array_slice($filas, 4, $movimientos->count()), fn ($f) => $f[0] !== null && $f[0] !== ''));
        $this->assertCount($movimientos->count(), $datos, 'El Excel debe tener una fila por cada movimiento de pantalla');
        $this->assertEqualsWithDelta(
            (float) $movimientos->sum('monto'),
            array_sum(array_map(fn ($f) => (float) $f[4], $datos)),
            0.001,
            'La suma de la columna Costo del Excel debe coincidir con la pantalla'
        );
        $anuladosExcel = count(array_filter($datos, fn ($f) => $f[6] === 'Sí'));
        $this->assertSame($movimientos->where('anulado', true)->count(), $anuladosExcel);

        $pie = collect($filas)->flatten()->first(fn ($c) => is_string($c) && str_starts_with($c, 'Balance Neto:'));
        $this->assertSame('Balance Neto: $ '.number_format($resumen['balance_neto'], 0, ',', '.'), $pie);

        // ── PDF: capturar los datos que recibe la plantilla al exportar
        $capturado = null;
        View::composer('reportes_financieros.pdf_diario', function ($vista) use (&$capturado) {
            $capturado = $vista->getData();
        });
        $pdf = $this->get(route('reportes.financiero.diario', ['fecha' => self::DIA, 'export' => 'pdf']))->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertNotNull($capturado, 'El PDF debe renderizar la plantilla reportes_financieros.pdf_diario');
        $this->assertEquals($resumen, $capturado['resumen'], 'El PDF debe usar exactamente el mismo resumen que la pantalla');
        $this->assertCount($movimientos->count(), $capturado['movimientos']);

        $html = view('reportes_financieros.pdf_diario', $capturado)->render();
        foreach (['total_ingresos', 'total_egresos', 'total_ventas', 'total_por_cobrar', 'monto_anulados'] as $clave) {
            $this->assertStringContainsString('$ '.number_format($resumen[$clave], 0, ',', '.'), $html, "PDF: {$clave}");
        }
        $this->assertStringContainsString(number_format($resumen['balance_neto'], 0, ',', '.'), $html);
    }
}
