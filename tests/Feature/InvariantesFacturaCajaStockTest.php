<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invariantes contables entre facturas, caja e inventario a lo largo de una secuencia de
 * operaciones con doble envío (doble clic / reenvío del formulario), anulación y reactivación:
 *
 *   I1. Para toda factura no anulada: total_pagado = Σ movimientos de caja activos ligados a ella
 *       (directos + abonos hijos), y estado = pendiente_pago ⇔ total_pagado < total_documento.
 *   I2. Para toda factura anulada: total_pagado = 0 y ningún movimiento ligado está activo.
 *   I3. Stock = inicial + Σ compras activas − Σ ventas activas.
 *   I4. Nunca se cobra más que el total del documento.
 *
 * Se comprueban tras CADA paso.
 */
class InvariantesFacturaCajaStockTest extends TestCase
{
    use RefreshDatabase;

    private const DIA = '2026-05-20';

    private const STOCK_INICIAL = 10;

    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 5, 20)->setTime(11, 0));
        $this->actingAs(User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]));
        $this->stock = Stock::create([
            'codigo' => 'SSD-480', 'producto' => 'Disco SSD 480GB', 'categoria' => 'Repuestos', 'cantidad' => self::STOCK_INICIAL,
            'precio_compra' => 25000, 'utilidad' => 60, 'precio_venta' => 40000, 'active' => true,
        ]);
    }

    private function verificarInvariantes(string $paso): void
    {
        foreach (Factura::all() as $f) {
            $ligados = MovimientoCaja::where(function ($q) use ($f) {
                $q->where('factura_id', $f->id)
                    ->orWhereIn('parent_id', MovimientoCaja::where('factura_id', $f->id)->pluck('id'));
            })->get();
            $activos = $ligados->where('anulado', false)->where('estado', 'activo');

            if ($f->estado === 'anulada') {
                $this->assertEqualsWithDelta(0, (float) $f->total_pagado, 0.001, "[{$paso}] I2 total_pagado {$f->numero_factura}");
                $this->assertCount(0, $activos, "[{$paso}] I2 movimientos activos en factura anulada {$f->numero_factura}");

                continue;
            }

            $this->assertEqualsWithDelta((float) $activos->sum('monto'), (float) $f->total_pagado, 0.001, "[{$paso}] I1 {$f->numero_factura}");
            $this->assertLessThanOrEqual((float) $f->total_documento + 0.001, (float) $f->total_pagado, "[{$paso}] I4 {$f->numero_factura}");
            $pendiente = (float) $f->total_documento - (float) $f->total_pagado > 0.001;
            $this->assertSame($pendiente ? 'pendiente_pago' : 'emitida', $f->estado, "[{$paso}] I1 estado {$f->numero_factura}");
        }

        $neto = 0;
        foreach (Factura::where('estado', '!=', 'anulada')->with('items')->get() as $f) {
            $cantidad = $f->items->where('stock_id', $this->stock->id)->sum('cantidad');
            $neto += $f->tipo_movimiento === 'compra' ? $cantidad : -$cantidad;
        }
        $this->assertSame(self::STOCK_INICIAL + $neto, (int) $this->stock->fresh()->cantidad, "[{$paso}] I3 stock");
    }

    private function venta(Cliente $cliente, int $cantidad, int $pagado)
    {
        return $this->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.$cliente->id, 'fecha' => self::DIA, 'total_pagado' => $pagado, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $this->stock->id, 'cantidad' => $cantidad, 'precio_unitario' => 40000]],
        ]);
    }

    public function test_invariantes_se_mantienen_con_doble_envio_anulacion_y_reactivacion(): void
    {
        $cliente = Cliente::factory()->create();
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '901000111',
            'nombre_razon_social' => 'Mayorista Tech', 'telefono' => '3015550000', 'active' => true,
        ]);

        // Venta a crédito: 3 x 40.000 = 120.000, cobra 20.000
        $this->venta($cliente, 3, 20000)->assertSessionMissing('error');
        $venta = Factura::where('tipo_movimiento', 'venta')->firstOrFail();
        $movVenta = MovimientoCaja::where('factura_id', $venta->id)->whereNull('parent_id')->firstOrFail();
        $this->verificarInvariantes('venta inicial');

        // Abono 50.000 desde caja → pagado 70.000
        $abono = ['monto_abono' => 50000, 'fecha' => self::DIA, 'tipo_pago' => 'efectivo'];
        $this->post(route('caja.abonos.store', $movVenta), $abono)->assertSessionHas('success');
        $this->assertEqualsWithDelta(70000, (float) $venta->fresh()->total_pagado, 0.001);
        $this->verificarInvariantes('abono 1');

        // Pago final 50.000 enviado DOS veces (doble clic): el segundo debe rechazarse
        $this->post(route('caja.abonos.store', $movVenta), $abono)->assertSessionHas('success');
        $this->post(route('caja.abonos.store', $movVenta), $abono)->assertSessionHas('error');
        $this->assertSame(2, MovimientoCaja::where('parent_id', $movVenta->id)->count());
        $this->assertSame('emitida', $venta->fresh()->estado);
        $this->verificarInvariantes('doble pago final');

        // Venta que pide más unidades de las disponibles: se rechaza sin tocar nada
        $this->venta($cliente, 50, 0)->assertSessionHas('error');
        $this->assertSame(1, Factura::where('tipo_movimiento', 'venta')->count());
        $this->verificarInvariantes('venta sin stock');

        // Compra de reposición pagada a medias: 5 x 25.000 = 125.000, paga 100.000
        $this->post(route('inventario.compra.store'), [
            'facturable_global' => 'Proveedor:'.$proveedor->id, 'fecha' => self::DIA, 'total_pagado' => 100000, 'tipo_pago' => 'consignacion',
            'items' => [['stock_id' => $this->stock->id, 'cantidad' => 5, 'precio_unitario' => 25000]],
        ])->assertSessionMissing('error');
        $this->verificarInvariantes('compra');

        // Anulación de la venta enviada dos veces con intención explícita
        $this->post(route('inventario.facturas.anular', $venta), ['motivo' => 'Prueba de anulación', 'accion' => 'anular'])->assertSessionHas('success');
        $this->post(route('inventario.facturas.anular', $venta), ['motivo' => 'Prueba de anulación', 'accion' => 'anular'])->assertSessionHas('error');
        $this->assertSame('anulada', $venta->fresh()->estado);
        $this->assertSame(0, MovimientoCaja::where('factura_id', $venta->id)->where('anulado', false)->count());
        $this->verificarInvariantes('doble anulación');

        // Reactivación (también doble): vuelven a contar los 3 pagos y sale de nuevo el stock
        $this->post(route('inventario.facturas.anular', $venta), ['motivo' => 'Prueba de anulación', 'accion' => 'reactivar'])->assertSessionHas('success');
        $this->post(route('inventario.facturas.anular', $venta), ['motivo' => 'Prueba de anulación', 'accion' => 'reactivar'])->assertSessionHas('error');
        $this->assertEqualsWithDelta(120000, (float) $venta->fresh()->total_pagado, 0.001);
        $this->verificarInvariantes('doble reactivación');

        // Anular SOLO un abono hijo (reembolso) → la factura vuelve a quedar pendiente por 50.000
        $hijo = MovimientoCaja::where('parent_id', $movVenta->id)->latest('id')->firstOrFail();
        $this->post(route('caja.anular', $hijo), ['motivo' => 'Prueba de anulación', 'accion' => 'anular'])->assertSessionHas('success');
        $this->assertEqualsWithDelta(70000, (float) $venta->fresh()->total_pagado, 0.001);
        $this->verificarInvariantes('anulación de un abono');

        // Caja del día cuadra con las facturas: ingresos = pagos activos de ventas
        $ingresos = (float) MovimientoCaja::whereDate('fecha', self::DIA)->where('anulado', false)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos = (float) MovimientoCaja::whereDate('fecha', self::DIA)->where('anulado', false)->where('tipo_movimiento', 'egreso')->sum('monto');
        $this->assertEqualsWithDelta((float) $venta->fresh()->total_pagado, $ingresos, 0.001);
        $this->assertEqualsWithDelta(100000, $egresos, 0.001);
    }
}
