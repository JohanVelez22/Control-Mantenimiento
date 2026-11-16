<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\User;
use App\Services\PosTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * X-10, X-11, X-14, X-15, X-17, X-18, X-19, X-20: Excel, PDF e impresión.
 */
class DocumentosExportacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($this->admin);
    }

    private function hojaDe($respuesta)
    {
        return IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getActiveSheet();
    }

    public function test_excel_de_ordenes_con_fechas_reales_documento_como_texto_y_totales_numericos(): void
    {
        $cliente = Cliente::factory()->create(['identificacion' => '0012345678']);
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id, 'proveedor_id' => null]);
        Mantenimiento::factory()->create(['equipo_id' => $equipo->id, 'costo' => 120000, 'anulado' => false,
            'fecha_entrada' => now()->toDateString(), 'fecha_salida' => null]);

        $hoja = $this->hojaDe($this->get(route('mantenimientos.reportes', ['export' => 'excel']))->assertOk());

        $this->assertSame('Identificación', $hoja->getCell('C4')->getValue());
        $this->assertSame('0012345678', $hoja->getCell('C5')->getValue(), 'La cédula conserva el cero inicial');
        $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('C5')->getDataType());
        $this->assertSame(DataType::TYPE_NUMERIC, $hoja->getCell('N5')->getDataType(), 'La fecha de entrada es una fecha de Excel');
        $this->assertSame('dd/mm/yyyy', $hoja->getStyle('N5')->getNumberFormat()->getFormatCode());
        $this->assertNull($hoja->getCell('O5')->getValue(), 'Sin fecha de salida la celda queda vacía');
        $this->assertSame('A4:O5', $hoja->getAutoFilter()->getRange());
        $this->assertSame('A5', $hoja->getFreezePane());

        $pie = collect($hoja->toArray(null, false, false, true))->first(fn ($f) => ($f['L'] ?? null) === 'Total (sin anuladas)');
        $this->assertNotNull($pie);
        $this->assertEquals(120000, $pie['M']);
    }

    public function test_excel_de_inventario_ordenado_como_la_pantalla(): void
    {
        $primero = Stock::factory()->create(['active' => true]);
        $segundo = Stock::factory()->create(['active' => true]);

        $hoja = $this->hojaDe($this->get(route('stocks.reportes', ['export' => 'excel']))->assertOk());

        $this->assertSame((string) $segundo->codigo, (string) $hoja->getCell('A5')->getValue());
        $this->assertSame((string) $primero->codigo, (string) $hoja->getCell('A6')->getValue());
        $this->assertTrue($hoja->getMergeCells() && in_array('A1:K1', array_values($hoja->getMergeCells()), true));
    }

    public function test_ticket_pos_largo_sale_en_una_sola_pagina(): void
    {
        $html = "@extends('layouts.print_pos')
@section('content')
<p>".str_repeat('Línea del ticket<br>', 400).'</p>
@endsection
';
        view()->addNamespace('pruebas', storage_path('framework/testing'));
        @mkdir(storage_path('framework/testing'), 0777, true);
        file_put_contents(storage_path('framework/testing/ticket.blade.php'), $html);

        $pdf = PosTicketService::streamTicket('pruebas::ticket', [], 't.pdf')->getContent();

        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdf), 'Un ticket POS debe ser una sola página');
        preg_match('/\/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+[\d.]+\s+([\d.]+)\s*\]/', $pdf, $caja);
        $this->assertGreaterThan(3500, (float) $caja[1], 'La altura se mide completa, más allá del lienzo anterior de 3500 pt');
    }

    public function test_cotizacion_en_a4_sin_recuadro_sin_logo_y_con_fuentes_reducidas(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-1', 'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'validez_dias' => 15, 'total' => 1000, 'estado' => 'pendiente', 'user_id' => $this->admin->id,
        ]);

        $pdf = $this->get(route('cotizaciones.pdf', $cotizacion))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[\s*0(\.0+)?\s+0(\.0+)?\s+595\.280?\s+841\.890?/', $pdf, 'Tamaño A4');
        $this->assertStringNotContainsString('SIN LOGO', $pdf);
        $this->assertLessThan(400 * 1024, strlen($pdf), 'Con subconjunto de fuentes el PDF pesa mucho menos de 900 KB');
    }

    public function test_botones_imprimir_de_reportes_abren_el_pdf_completo(): void
    {
        $this->get(route('mantenimientos.reportes'))->assertOk()
            ->assertSee('export=pdf', false)
            ->assertDontSee('onclick="window.print()"', false);
    }
}
