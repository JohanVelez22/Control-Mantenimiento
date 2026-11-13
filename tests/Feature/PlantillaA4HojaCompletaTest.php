<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * X-02: la plantilla A4 de media hoja se rompía con muchos ítems (firmas y pie encima de
 * las filas, totales partidos entre páginas, marca ANULADA solo en la última página).
 * Se renderiza el PDF real con dompdf y se registra la página y la altura de cada texto.
 */
class PlantillaA4HojaCompletaTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{paginas: int, textos: array<int, array{texto: string, pagina: int, y: float}>} */
    private function renderizar(Mantenimiento $orden): array
    {
        $orden->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        return $this->renderizarVista('mantenimientos.factura', ['mantenimiento' => $orden]);
    }

    private function renderizarVista(string $vista, array $datos): array
    {
        $pdf = Pdf::loadView($vista, $datos);
        $pdf->setPaper('a4', 'portrait');
        $dompdf = $pdf->getDomPDF();
        $textos = [];
        $dompdf->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($frame) use (&$textos, $dompdf) {
                $nodo = $frame->get_node();
                if ($nodo->nodeName === '#text' && trim($nodo->nodeValue) !== '') {
                    $textos[] = [
                        'texto' => trim($nodo->nodeValue),
                        'pagina' => $dompdf->getCanvas()->get_page_number(),
                        'y' => (float) $frame->get_position('y'),
                    ];
                }
            },
        ]]);
        $dompdf->render();

        return ['paginas' => $dompdf->getCanvas()->get_page_count(), 'textos' => $textos];
    }

    private function buscar(array $textos, string $aguja): array
    {
        return array_values(array_filter($textos, fn ($t) => str_contains($t['texto'], $aguja)));
    }

    private function ordenConRepuestos(int $n, bool $anulada = false): Mantenimiento
    {
        $orden = Mantenimiento::factory()->create([
            'costo' => 0, 'anulado' => $anulada, 'estado' => 'terminado', 'fecha_salida' => now()->toDateString(),
        ]);
        foreach (range(1, $n) as $i) {
            $stock = Stock::create([
                'codigo' => "R-{$i}", 'producto' => "Repuesto numero {$i}", 'categoria' => 'C', 'subcategoria' => 'S',
                'cantidad' => 5, 'precio_compra' => 1000, 'utilidad' => 10, 'precio_venta' => 1100, 'active' => true,
            ]);
            $orden->stocks()->attach($stock->id, ['cantidad' => 1, 'precio_unitario' => 1100]);
        }
        $orden->update(['costo' => 1100 * $n]);

        return $orden->fresh();
    }

    public function test_con_muchos_items_las_firmas_y_el_pie_quedan_despues_de_los_items(): void
    {
        $r = $this->renderizar($this->ordenConRepuestos(40));

        $this->assertGreaterThanOrEqual(1, $r['paginas']);
        $ultimoItem = $this->buscar($r['textos'], 'Repuesto numero 40')[0];
        $firma = $this->buscar($r['textos'], 'Firma Cliente')[0];
        $pie = $this->buscar($r['textos'], 'soporte válido')[0];

        // La firma va en la misma página o en una posterior, y siempre debajo del último ítem.
        $this->assertTrue(
            $firma['pagina'] > $ultimoItem['pagina'] || $firma['y'] > $ultimoItem['y'],
            'Las firmas quedaron encima de los ítems: '.json_encode([$ultimoItem, $firma])
        );
        $this->assertTrue($pie['pagina'] > $firma['pagina'] || $pie['y'] > $firma['y'], 'El pie quedó encima de las firmas');
        $this->assertSame($r['paginas'], $pie['pagina'], 'El pie debe ir en la última página');

        // Ningún ítem comparte altura con las firmas en la misma página (no hay superposición).
        foreach ($this->buscar($r['textos'], 'Repuesto numero') as $item) {
            if ($item['pagina'] === $firma['pagina']) {
                $this->assertLessThan($firma['y'], $item['y']);
            }
        }
    }

    public function test_los_totales_no_se_parten_entre_paginas(): void
    {
        $r = $this->renderizar($this->ordenConRepuestos(40));

        $costo = $this->buscar($r['textos'], 'Costo Total Estimado')[0];
        $saldo = $this->buscar($r['textos'], 'SALDO PENDIENTE')[0];
        $this->assertSame($costo['pagina'], $saldo['pagina']);
    }

    public function test_la_marca_anulada_sale_en_todas_las_paginas(): void
    {
        $r = $this->renderizar($this->ordenConRepuestos(60, anulada: true));

        $this->assertGreaterThan(1, $r['paginas']);
        $paginasConMarca = array_unique(array_column($this->buscar($r['textos'], 'ANULADA'), 'pagina'));
        $this->assertCount($r['paginas'], $paginasConMarca);
    }

    public function test_con_pocos_items_se_conserva_la_media_hoja(): void
    {
        $r = $this->renderizar($this->ordenConRepuestos(3));

        $this->assertSame(1, $r['paginas']);
        // Las firmas siguen en la mitad superior de la hoja (141 mm ≈ 400 pt).
        $this->assertLessThan(400, $this->buscar($r['textos'], 'Firma Cliente')[0]['y']);
    }

    private function assertFirmasDespuesDe(array $r, string $ultimoItem, string $firma): void
    {
        $item = $this->buscar($r['textos'], $ultimoItem)[0];
        $f = $this->buscar($r['textos'], $firma)[0];
        $this->assertTrue($f['pagina'] > $item['pagina'] || $f['y'] > $item['y'], 'Firmas encima de los ítems: '.json_encode([$item, $f]));
    }

    public function test_factura_de_inventario_con_muchos_items(): void
    {
        $factura = new Factura(['numero_factura' => 'VT-9', 'tipo_movimiento' => 'venta', 'fecha' => now(), 'total_documento' => 60000, 'estado' => 'emitida']);
        $factura->setRelation('facturable', Cliente::factory()->create());
        $factura->setRelation('user', User::factory()->create());
        $factura->setRelation('items', collect(range(1, 60))->map(function ($i) {
            $item = new FacturaItem(['cantidad' => 1, 'precio_unitario' => 1000, 'descripcion' => "Producto numero {$i}"]);
            $item->setRelation('stock', null);

            return $item;
        }));

        $r = $this->renderizarVista('inventario.facturas.print', ['factura' => $factura, 'abonos' => collect()]);

        $this->assertGreaterThan(1, $r['paginas']);
        $this->assertFirmasDespuesDe($r, 'Producto numero 60', 'Firma Cliente');
    }

    public function test_cotizacion_con_muchos_items(): void
    {
        $cot = Cotizacion::create([
            'codigo' => 'COT-9', 'cliente_id' => Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'validez_dias' => 15, 'total' => 60000, 'estado' => 'pendiente', 'user_id' => User::factory()->create()->id,
        ]);
        foreach (range(1, 60) as $i) {
            CotizacionItem::create([
                'cotizacion_id' => $cot->id, 'tipo' => 'libre', 'descripcion' => "Servicio numero {$i}",
                'cantidad' => 1, 'precio_unitario' => 1000, 'subtotal' => 1000,
            ]);
        }

        $r = $this->renderizarVista('cotizaciones.pdf', ['cotizacion' => $cot->fresh()->load(['items', 'user']), 'empresa' => null]);

        $this->assertGreaterThan(1, $r['paginas']);
        $this->assertFirmasDespuesDe($r, 'Servicio numero 60', 'Aprobación del');
    }
}
