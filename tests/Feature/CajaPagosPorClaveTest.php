<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * D-05 / M-02: en caja, el total pagado y el saldo de un movimiento se calculan con
 * claves (factura, orden, movimiento raíz), no buscando referencias en el texto.
 */
class CajaPagosPorClaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    public function test_pago_de_orden_muestra_el_saldo_de_la_orden(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 100000, 'anulado' => false, 'estado' => 'pendiente']);
        foreach ([30000, 20000] as $monto) {
            $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
                'monto' => $monto, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
            ])->assertSessionHasNoErrors();
        }

        $mov = MovimientoCaja::whereNotNull('abono_id')->orderBy('id')->firstOrFail();
        $this->assertEquals(100000, $mov->effective_monto_total);
        $this->assertEquals(50000, $mov->total_pagado);
        $this->assertEquals(50000, $mov->saldo_pendiente);
        $this->assertEquals($orden->fresh()->saldo_pendiente, $mov->saldo_pendiente);
    }

    public function test_pago_de_factura_muestra_el_saldo_de_la_factura_y_no_suma_ingresos_que_la_mencionan(): void
    {
        $stock = Stock::create([
            'codigo' => 'R-1', 'producto' => 'Mouse', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => 5, 'precio_compra' => 10000, 'utilidad' => 50, 'precio_venta' => 15000, 'active' => true,
        ]);
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::factory()->create()->id, 'fecha' => now()->toDateString(),
            'total_pagado' => 10000, 'tipo_pago' => 'efectivo',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => 15000]],
        ])->assertSessionHasNoErrors();
        $pago = MovimientoCaja::whereNotNull('factura_id')->firstOrFail();

        // Un ingreso manual que menciona la factura no es un pago de ella.
        MovimientoCaja::create([
            'persona' => 'Otro', 'fecha' => now()->toDateString(), 'concepto_id' => ConceptoCaja::create(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 20000,
            'descripcion' => 'Nota sobre '.$pago->descripcion, 'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
        ]);

        $pago->refresh();
        $this->assertEquals(30000, $pago->effective_monto_total);
        $this->assertEquals(10000, $pago->total_pagado);
        $this->assertEquals(20000, $pago->saldo_pendiente);
    }

    public function test_listado_de_caja_no_dispara_consultas_por_texto(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Servicio']);
        foreach (range(1, 10) as $i) {
            MovimientoCaja::create([
                'persona' => "P{$i}", 'fecha' => now()->toDateString(), 'concepto_id' => $concepto->id,
                'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000,
                'descripcion' => "Cobro Orden ORD-{$i} #VT-{$i}", 'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
            ]);
        }

        DB::enableQueryLog();
        $this->actingAs($this->admin)->get(route('caja.index'))->assertOk();
        $consultas = collect(DB::getQueryLog())->pluck('query');

        $this->assertSame(0, $consultas->filter(fn ($q) => str_contains($q, '"descripcion" like'))->count());
        // Antes: una consulta a facturas/órdenes por fila para leer "#VT-n" u "Orden ORD-n" del texto.
        $porFila = $consultas->filter(fn ($q) => preg_match('/from "(facturas|mantenimientos)" where "(numero_factura|id_orden)"/', $q));
        $this->assertSame(0, $porFila->count(), $porFila->implode(' | '));
    }
}
