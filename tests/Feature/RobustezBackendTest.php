<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\OrdenService;
use App\View\Composers\NotificacionesComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cuarta ronda (robustez): validaciones de entrada, reglas de negocio y consultas.
 * Cada prueba falla con el código anterior a esta ronda.
 */
class RobustezBackendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    private function orden(array $datos = []): Mantenimiento
    {
        return Mantenimiento::factory()->create($datos + [
            'costo' => 100000, 'anulado' => false, 'estado' => 'pendiente',
            'fecha_entrada' => now()->subDays(2)->toDateString(), 'fecha_salida' => null,
        ]);
    }

    // ── B-05 / S-16: entradas armadas a mano ya no producen errores 500 ─────────────

    public function test_filtros_con_arreglos_o_fechas_invalidas_no_producen_error_500(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('reportes.financiero.diario', ['fecha' => 'xyz']))->assertOk();
        $this->get(route('reportes.financiero.acumulado', ['desde' => 'xyz', 'hasta' => '2026-02-30']))->assertOk();
        $this->get(route('reportes.financiero.operaciones', ['desde' => 'xyz']))->assertOk();
        $this->get(route('mantenimientos.reportes', ['search' => ['a']]))->assertOk();
        $this->get(route('electronicas.index', ['search' => ['a']]))->assertOk();
        $this->get(route('caja.index', ['search' => ['a']]))->assertOk();
        $this->getJson(route('api.municipios', ['departamento' => ['x']]))->assertOk()->assertExactJson([]);
    }

    public function test_tipo_de_operaciones_desconocido_se_rechaza_sin_error_500(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.financiero.operaciones', ['tipo' => 'otro', 'export' => 'pdf']))
            ->assertRedirect()->assertSessionHasErrors('tipo');
    }

    public function test_quitar_repuesto_con_id_no_numerico_responde_404(): void
    {
        $orden = $this->orden();
        $this->actingAs($this->admin)->delete('/mantenimientos/'.$orden->id.'/stocks/abc')->assertNotFound();
    }

    public function test_venta_con_destinatario_mal_formado_o_inexistente_se_rechaza(): void
    {
        $stock = Stock::factory()->create(['cantidad' => 5, 'active' => true]);
        $datos = fn ($destino) => [
            'facturable_global' => $destino, 'fecha' => now()->toDateString(), 'total_pagado' => 0,
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 1000]],
        ];

        $this->actingAs($this->admin)->post(route('inventario.venta.store'), $datos('X'))->assertSessionHasErrors('facturable_global');
        $this->post(route('inventario.venta.store'), $datos('Cliente:999999'))->assertSessionHasErrors('facturable_global');
        $this->assertSame(0, Factura::count());
    }

    public function test_venta_de_producto_anulado_se_rechaza(): void
    {
        $stock = Stock::factory()->create(['cantidad' => 5, 'active' => false]);

        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::factory()->create()->id, 'fecha' => now()->toDateString(), 'total_pagado' => 0,
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 1000]],
        ])->assertSessionHasErrors('items.0.stock_id');
        $this->assertSame(5, (int) $stock->fresh()->cantidad);
    }

    public function test_clave_de_admin_como_arreglo_no_produce_type_error(): void
    {
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        $orden = $this->orden();

        $this->actingAs($tecnico)->put(route('mantenimientos.update', $orden), [
            'fecha_entrada' => $orden->fecha_entrada->toDateString(), 'tipo' => 'preventivo', 'reparacion' => 'software',
            'descripcion' => 'Revisión general', 'costo' => 100000, 'estado' => 'pendiente',
            'equipo_id' => $orden->equipo_id, 'tecnico_id' => $orden->tecnico_id, 'admin_password' => ['x'],
        ])->assertSessionHasErrors('admin_password');
    }

    // ── B-07 / B-18: abonos ─────────────────────────────────────────────────────────

    public function test_abono_normaliza_el_monto_en_el_servidor(): void
    {
        $orden = $this->orden();

        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => '1.000', 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1000, (float) Abono::firstOrFail()->monto);
    }

    public function test_abono_con_fecha_futura_o_anterior_al_ingreso_se_rechaza(): void
    {
        $orden = $this->orden();

        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => 1000, 'fecha' => now()->addDay()->toDateString(), 'tipo_pago' => 'efectivo',
        ])->assertSessionHasErrors('fecha');
        $this->post(route('abonos.store', $orden), [
            'monto' => 1000, 'fecha' => now()->subDays(10)->toDateString(), 'tipo_pago' => 'efectivo',
        ])->assertSessionHasErrors('fecha');

        $this->assertSame(0, Abono::count());
    }

    public function test_abono_de_equipo_de_proveedor_registra_al_proveedor_en_caja(): void
    {
        $proveedor = Proveedor::create([
            'nombre_razon_social' => 'Distribuidora Uno', 'identificacion' => '900123', 'tipo_identificacion' => 'nit', 'active' => true,
        ]);
        $equipo = Equipo::factory()->create(['cliente_id' => null, 'proveedor_id' => $proveedor->id]);
        $orden = $this->orden(['equipo_id' => $equipo->id]);

        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => 5000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Distribuidora Uno', MovimientoCaja::whereNotNull('abono_id')->firstOrFail()->persona);
    }

    public function test_anular_abono_por_la_ruta_del_otro_modulo_responde_404(): void
    {
        $orden = $this->orden();
        $this->actingAs($this->admin)->post(route('abonos.store', $orden), [
            'monto' => 5000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo',
        ]);
        $abono = Abono::firstOrFail();

        $this->delete(route('electronicas.abonos.destroy', $abono), ['motivo' => 'Prueba de anulación'])->assertNotFound();
        $this->assertFalse((bool) $abono->fresh()->anulado);

        $this->delete(route('abonos.destroy', $abono), ['motivo' => 'Prueba de anulación'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'anulado'));
        $this->assertTrue((bool) $abono->fresh()->anulado);
    }

    // ── B-06 / B-07 / B-21: órdenes ────────────────────────────────────────────────

    public function test_orden_no_acepta_tecnico_inactivo_ni_fecha_de_entrada_futura(): void
    {
        $inactivo = Tecnico::factory()->create(['active' => false]);
        $equipo = Equipo::factory()->create();

        $this->actingAs($this->admin)->post(route('mantenimientos.store'), [
            'fecha_entrada' => now()->addDays(3)->toDateString(), 'tipo' => 'preventivo', 'reparacion' => 'software',
            'descripcion' => 'Revisión', 'costo' => 50000, 'estado' => 'pendiente',
            'equipo_id' => $equipo->id, 'tecnico_id' => $inactivo->id,
        ])->assertSessionHasErrors(['tecnico_id', 'fecha_entrada']);
        $this->assertSame(0, Mantenimiento::count());
    }

    public function test_orden_existente_conserva_su_tecnico_aunque_este_inactivo(): void
    {
        $orden = $this->orden();
        $orden->tecnico->update(['active' => false]);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $orden), [
            'fecha_entrada' => $orden->fecha_entrada->toDateString(), 'tipo' => 'preventivo', 'reparacion' => 'software',
            'descripcion' => 'Revisión con cambio', 'costo' => 100000, 'estado' => 'pendiente',
            'equipo_id' => $orden->equipo_id, 'tecnico_id' => $orden->tecnico_id,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Revisión con cambio', $orden->fresh()->descripcion);
    }

    public function test_orden_con_abonos_no_cambia_de_equipo(): void
    {
        $orden = $this->orden();
        $this->actingAs($this->admin)->post(route('abonos.store', $orden), ['monto' => 5000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo']);
        $otro = Equipo::factory()->create();

        $this->put(route('mantenimientos.update', $orden), [
            'fecha_entrada' => $orden->fecha_entrada->toDateString(), 'tipo' => 'preventivo', 'reparacion' => 'software',
            'descripcion' => 'Revisión', 'costo' => 100000, 'estado' => 'pendiente',
            'equipo_id' => $otro->id, 'tecnico_id' => $orden->tecnico_id,
        ])->assertSessionHasErrors('equipo_id');
        $this->assertSame($orden->equipo_id, $orden->fresh()->equipo_id);
    }

    public function test_equipo_con_orden_cobrada_no_cambia_de_propietario(): void
    {
        $orden = $this->orden();
        $this->actingAs($this->admin)->post(route('abonos.store', $orden), ['monto' => 5000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo']);
        $equipo = $orden->equipo;
        $nuevo = Cliente::factory()->create();

        $this->put(route('equipos.update', $equipo), [
            'nombre' => $equipo->nombre, 'marca' => $equipo->marca, 'modelo' => $equipo->modelo, 'serie' => $equipo->serie,
            'propietario_global' => 'Cliente:'.$nuevo->id,
        ])->assertSessionHasErrors('propietario_global');
        $this->assertNotSame($nuevo->id, $equipo->fresh()->cliente_id);
    }

    // ── B-08: cotizaciones ─────────────────────────────────────────────────────────

    public function test_cotizacion_no_se_enlaza_con_la_factura_de_otra_por_prefijo(): void
    {
        $cliente = Cliente::factory()->create();
        $factura = Factura::create([
            'numero_factura' => 'VT-9', 'tipo_movimiento' => 'venta', 'estado' => 'emitida',
            'facturable_type' => Cliente::class, 'facturable_id' => $cliente->id,
            'total_documento' => 1000, 'total_pagado' => 1000, 'fecha' => now()->toDateString(), 'user_id' => $this->admin->id,
            'observaciones' => 'Venta generada automáticamente desde Cotización #COT-10',
        ]);
        $cot1 = Cotizacion::create(['codigo' => 'COT-1', 'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(), 'validez_dias' => 15, 'total' => 1000, 'estado' => 'pendiente', 'user_id' => $this->admin->id]);
        $cot10 = Cotizacion::create(['codigo' => 'COT-10', 'cliente_id' => $cliente->id, 'fecha' => now()->toDateString(), 'validez_dias' => 15, 'total' => 1000, 'estado' => 'aprobada', 'user_id' => $this->admin->id, 'factura_id' => $factura->id]);

        $this->assertNull($cot1->factura_creada);
        $this->assertSame($factura->id, $cot10->factura_creada->id);
    }

    // ── B-20: conceptos de sistema ─────────────────────────────────────────────────

    public function test_concepto_del_sistema_no_se_renombra(): void
    {
        $concepto = ConceptoCaja::create(['nombre' => 'Pago Mantenimiento']);

        $this->actingAs($this->admin)->put(route('conceptos.update', $concepto), ['nombre' => 'Pagos varios'])->assertSessionHas('error');
        $this->assertSame('Pago Mantenimiento', $concepto->fresh()->nombre);
    }

    // ── B-09: anular producto con intención explícita ──────────────────────────────

    public function test_doble_clic_en_anular_producto_no_lo_reactiva(): void
    {
        $stock = Stock::factory()->create(['active' => true]);

        $this->actingAs($this->admin)->post(route('stocks.anular', $stock), ['accion' => 'anular']);
        $this->post(route('stocks.anular', $stock), ['accion' => 'anular'])->assertSessionHas('error');

        $this->assertFalse((bool) $stock->fresh()->active);
    }

    // ── B-15 / D-17: numeración ────────────────────────────────────────────────────

    public function test_vista_previa_del_numero_no_escribe_en_la_base(): void
    {
        DB::table('secuencias_documento')->delete();

        $this->actingAs($this->admin)->get(route('mantenimientos.create'))->assertOk();

        $this->assertSame(0, DB::table('secuencias_documento')->count());
    }

    public function test_numeracion_considera_documentos_borrados_logicamente(): void
    {
        $cliente = Cliente::factory()->create();
        $factura = Factura::create([
            'numero_factura' => 'VT-10', 'tipo_movimiento' => 'venta', 'estado' => 'emitida',
            'facturable_type' => Cliente::class, 'facturable_id' => $cliente->id,
            'total_documento' => 0, 'total_pagado' => 0, 'fecha' => now()->toDateString(), 'user_id' => $this->admin->id,
        ]);
        $factura->delete();
        DB::table('secuencias_documento')->delete();

        $this->assertSame('VT-11', DB::transaction(fn () => app(OrdenService::class)->siguiente('VT-', Factura::class, 'numero_factura')));
    }

    // ── B-22 / F-05: notificaciones ────────────────────────────────────────────────

    public function test_contadores_de_notificaciones_no_se_cortan_en_50(): void
    {
        Mantenimiento::factory()->count(55)->create(['anulado' => false, 'estado' => 'pendiente', 'fecha_entrada' => now()->subDays(5)->toDateString()]);
        NotificacionesComposer::invalidar();

        $this->actingAs($this->admin);

        $this->assertSame(55, $this->datosLayout('mantPendientes'));
        $this->assertSame(55, $this->datosLayout('atrasadas'));
    }

    public function test_notificaciones_se_refrescan_al_guardar_una_orden(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'));
        $this->assertSame(0, $this->datosLayout('mantPendientes'));

        $this->orden();

        $this->assertSame(1, $this->datosLayout('mantPendientes'));
    }

    private function datosLayout(string $clave): mixed
    {
        $vista = view('layouts.app');
        app(NotificacionesComposer::class)->compose($vista);

        return $vista->getData()[$clave] ?? null;
    }

    // ── B-13 / ARQ-12: stock bajo ──────────────────────────────────────────────────

    public function test_stock_bajo_no_cuenta_productos_anulados(): void
    {
        Stock::factory()->create(['cantidad' => 1, 'active' => false]);
        Stock::factory()->create(['cantidad' => 2, 'active' => true]);
        Stock::factory()->create(['cantidad' => 50, 'active' => true]);

        $this->assertSame(1, Stock::activos()->bajoMinimo()->count());
    }

    // ── B-07 / B-10: reporte de electrónica ────────────────────────────────────────

    public function test_reporte_de_electronica_usa_el_mes_actual_por_defecto(): void
    {
        Electronica::factory()->create(['fecha_entrada' => now()->subMonths(3)->toDateString(), 'anulado' => false]);
        Electronica::factory()->create(['fecha_entrada' => now()->toDateString(), 'anulado' => false]);

        $vista = $this->actingAs($this->admin)->get(route('electronicas.reportes'))->assertOk();

        $this->assertSame(1, $vista->viewData('totales')['cantidad']);
    }

    // ── B-04 / B-14: historial ─────────────────────────────────────────────────────

    public function test_historial_filtra_por_registro_y_resuelve_solo_los_ids_mostrados(): void
    {
        $orden = $this->orden();
        Evento::registrar('prueba', $orden, null, ['tecnico_id' => $orden->tecnico_id], 'Evento de la orden');
        Evento::registrar('prueba', $this->admin, null, null, 'Evento de otro registro');

        $vista = $this->actingAs($this->admin)->get(route('eventos.index', [
            'modelo_tipo' => Mantenimiento::class, 'modelo_id' => $orden->id,
        ]))->assertOk();

        $this->assertTrue($vista->viewData('eventos')->every(fn ($e) => $e->modelo_id === $orden->id && $e->modelo_tipo === Mantenimiento::class));
        $this->assertSame([$orden->tecnico_id => $orden->tecnico->nombre], $vista->viewData('lookups')['tecnicos']);
        $this->assertSame([], $vista->viewData('lookups')['stocks']);
    }
}
