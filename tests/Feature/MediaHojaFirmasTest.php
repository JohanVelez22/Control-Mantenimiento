<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recorrido de punta a punta (16/11/2026): con el encabezado completo de la empresa y la
 * línea de mano de obra, la orden impresa en media hoja quedaba con las líneas de firma
 * encima del último abono. Se renderiza el PDF real para cada combinación de repuestos,
 * abonos y descripción, y se mide que la firma quede siempre debajo de todo el contenido.
 */
class MediaHojaFirmasTest extends TestCase
{
    use RefreshDatabase;

    private const RENGLON = 12.0; // alto aproximado de una fila de la tabla, en puntos

    protected function setUp(): void
    {
        parent::setUp();
        // Encabezado como el real: cuatro renglones de datos de la empresa.
        Configuracion::create([
            'nombre' => 'Tecni Systemas', 'nit' => '4.501.927', 'telefono' => '3172697442 - 3165528637',
            'direccion' => 'Cra 4 # 20-81 Pereira', 'correo' => 'tecnisystemaspereira@hotmail.com',
        ]);
    }

    /** Distancia entre el último texto del contenido y la línea de firma, en la página de la firma. */
    private function holgura(string $vista, string $variable, Model $orden): float
    {
        $orden->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks']);

        return $this->holguraDe($vista, [$variable => $orden], 'Firma Cliente');
    }

    /** @param  array<string, mixed>  $datos */
    private function holguraDe(string $vista, array $datos, string $rotuloFirma): float
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
                    $textos[] = [trim($nodo->nodeValue), $dompdf->getCanvas()->get_page_number(), (float) $frame->get_position('y')];
                }
            },
        ]]);
        $dompdf->render();

        $firma = collect($textos)->first(fn ($t) => str_contains($t[0], $rotuloFirma));
        $this->assertNotNull($firma);
        $ultimo = collect($textos)
            ->filter(fn ($t) => $t[1] === $firma[1])
            ->reject(fn ($t) => preg_match('/Firma|Aprobación del Cliente|^Tecni Systemas$|Gracias|soporte válido|DIAN/u', $t[0]))
            ->max(fn ($t) => $t[2]);

        return $firma[2] - $ultimo;
    }

    private function armar(Model $orden, int $repuestos, int $abonos, string $campoOrden): Model
    {
        foreach (range(1, max(1, $repuestos)) as $i) {
            if ($repuestos === 0) {
                break;
            }
            $stock = Stock::create([
                'codigo' => uniqid('R'), 'producto' => "Repuesto {$i}", 'categoria' => 'C', 'subcategoria' => 'S',
                'cantidad' => 5, 'precio_compra' => 1000, 'utilidad' => 10, 'precio_venta' => 1100, 'active' => true,
            ]);
            $orden->stocks()->attach($stock->id, ['cantidad' => 1, 'precio_unitario' => 1100]);
        }
        $orden->update(['costo' => 1100 * $repuestos + 50000]); // con mano de obra
        foreach (range(1, max(1, $abonos)) as $i) {
            if ($abonos === 0) {
                break;
            }
            Abono::create([$campoOrden => $orden->id, 'monto' => 1000, 'fecha' => now()->toDateString(),
                'tipo_pago' => $i % 2 ? 'efectivo' : 'consignacion', 'user_id' => $orden->user_id, 'descripcion' => "Abono a cuenta {$i}"]);
        }

        return $orden->fresh();
    }

    public function test_en_la_orden_de_mantenimiento_la_firma_nunca_queda_encima_del_contenido(): void
    {
        foreach ([0, 1, 2, 3, 4] as $repuestos) {
            foreach ([0, 1, 2, 3, 4] as $abonos) {
                foreach ([20, 260] as $largo) {
                    $orden = Mantenimiento::factory()->create([
                        'estado' => 'terminado', 'fecha_salida' => now()->toDateString(), 'descripcion' => str_repeat('revisar ', $largo / 8),
                    ]);
                    $orden = $this->armar($orden, $repuestos, $abonos, 'mantenimiento_id');
                    $holgura = $this->holgura('mantenimientos.factura', 'mantenimiento', $orden);
                    $this->assertGreaterThanOrEqual(self::RENGLON + 4, $holgura, "repuestos={$repuestos} abonos={$abonos} descripción={$largo}");
                }
            }
        }
    }

    public function test_en_la_orden_de_electronica_la_firma_nunca_queda_encima_del_contenido(): void
    {
        foreach ([[1, 2], [2, 2], [1, 4], [4, 1], [0, 4]] as [$repuestos, $abonos]) {
            $orden = Electronica::factory()->create(['estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);
            $orden = $this->armar($orden, $repuestos, $abonos, 'electronica_id');
            $holgura = $this->holgura('electronicas.factura', 'electronica', $orden);
            $this->assertGreaterThanOrEqual(self::RENGLON + 4, $holgura, "repuestos={$repuestos} abonos={$abonos}");
        }
    }

    public function test_en_la_factura_de_venta_la_firma_nunca_queda_encima_del_contenido(): void
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        foreach ([1, 3, 5, 6, 8, 9] as $items) {
            foreach ([0, 1, 3] as $abonos) {
                $factura = Factura::create([
                    'numero_factura' => uniqid('VT-'), 'tipo_movimiento' => 'venta', 'estado' => 'emitida',
                    'facturable_id' => $cliente->id, 'facturable_type' => Cliente::class,
                    'total_documento' => 1100 * $items, 'total_pagado' => 0, 'fecha' => now()->toDateString(),
                    'user_id' => $usuario->id, 'observaciones' => 'Entrega inmediata',
                ]);
                foreach (range(1, $items) as $i) {
                    FacturaItem::create(['factura_id' => $factura->id, 'descripcion' => "Producto {$i}", 'cantidad' => 1, 'precio_unitario' => 1100]);
                }
                $pagos = collect(range(1, 3))->take($abonos)->map(fn ($i) => new MovimientoCaja([
                    'fecha' => now(), 'monto' => 100, 'tipo_pago' => 'efectivo', 'descripcion' => "Abono {$i}",
                ]));
                $factura->load(['facturable', 'items.stock', 'user']);
                $holgura = $this->holguraDe('inventario.facturas.print', ['factura' => $factura, 'abonos' => $pagos], 'Firma Cliente');
                $this->assertGreaterThanOrEqual(self::RENGLON + 4, $holgura, "ítems={$items} abonos={$abonos}");
            }
        }
    }

    public function test_en_la_cotizacion_la_firma_nunca_queda_encima_del_contenido(): void
    {
        $cliente = Cliente::factory()->create();
        $usuario = User::factory()->create();
        foreach ([1, 4, 6, 8, 9] as $items) {
            foreach (['', str_repeat('Garantía de tres meses. ', 12)] as $notas) {
                $cotizacion = Cotizacion::create([
                    'codigo' => uniqid('COT-'), 'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(),
                    'validez_dias' => 15, 'total' => 1100 * $items, 'estado' => 'pendiente', 'notas' => $notas, 'user_id' => $usuario->id,
                ]);
                foreach (range(1, $items) as $i) {
                    CotizacionItem::create(['cotizacion_id' => $cotizacion->id, 'tipo' => 'libre', 'descripcion' => "Servicio {$i}", 'cantidad' => 1, 'precio_unitario' => 1100, 'subtotal' => 1100]);
                }
                $cotizacion->load('cliente', 'proveedor', 'items.stock', 'user');
                $holgura = $this->holguraDe('cotizaciones.pdf', ['cotizacion' => $cotizacion, 'empresa' => Configuracion::first()], 'Aprobación del Cliente');
                $this->assertGreaterThanOrEqual(self::RENGLON + 4, $holgura, "ítems={$items} notas=".mb_strlen($notas));
            }
        }
    }
}
