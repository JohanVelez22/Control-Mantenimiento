<?php

namespace Tests\Feature;

use App\Exports\SafeValueBinder;
use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\AnulacionService;
use App\Services\OrdenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * Regresiones de la auditoría de noviembre 2026 (integridad financiera y de estados).
 * Cada test reproduce un escenario que antes producía dinero fantasma o un estado incoherente.
 */
class AuditoriaIntegridadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Mantenimiento $mantenimiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);

        $cliente = Cliente::factory()->create();
        $equipo = Equipo::factory()->create(['cliente_id' => $cliente->id]);

        $this->mantenimiento = Mantenimiento::factory()->create([
            'equipo_id' => $equipo->id,
            'tecnico_id' => Tecnico::factory()->create()->id,
            'user_id' => $this->admin->id,
            'anulado' => false,
            'estado' => 'pendiente',
            'costo' => 100000,
        ]);
    }

    private function abonar(int $monto): void
    {
        $this->actingAs($this->admin)->post(route('abonos.store', $this->mantenimiento), [
            'monto' => $monto,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ])->assertRedirect();
    }

    private function ingresosActivos(): float
    {
        return (float) MovimientoCaja::where('tipo_movimiento', 'ingreso')
            ->where('anulado', false)->where('estado', 'activo')->sum('monto');
    }

    /** Pago total sin concepto "Abono Mantenimiento" sembrado: anular la orden debe anular su ingreso en caja. */
    public function test_anular_orden_con_pago_total_anula_el_ingreso_en_caja(): void
    {
        $this->abonar(100000);
        $this->assertEquals(100000, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento))->assertRedirect();

        $this->assertTrue((bool) $this->mantenimiento->fresh()->anulado);
        $this->assertEquals(0, $this->ingresosActivos());
    }

    /** Un abono eliminado antes de anular la orden no debe revivir al reactivarla. */
    public function test_reactivar_orden_no_revive_abonos_eliminados_individualmente(): void
    {
        $this->abonar(30000);
        $abonoEliminado = Abono::first();
        $this->actingAs($this->admin)->delete(route('abonos.destroy', $abonoEliminado))->assertRedirect();

        $this->abonar(50000);
        $this->assertEquals(50000, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento));
        $this->assertEquals(0, $this->ingresosActivos());

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento));
        $this->assertFalse((bool) $this->mantenimiento->fresh()->anulado);

        $this->assertTrue($abonoEliminado->fresh()->anulado, 'El abono eliminado no debe reactivarse');
        $this->assertEquals(50000, $this->ingresosActivos());
        $this->assertEquals(50000, $this->mantenimiento->fresh()->total_abonado);
    }

    /** Doble envío del formulario de anulación: la segunda petición no debe reactivar la orden. */
    public function test_doble_envio_de_anulacion_no_invierte_la_operacion(): void
    {
        $this->abonar(40000);

        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento), ['accion' => 'anular']);
        $this->actingAs($this->admin)->post(route('mantenimientos.anular', $this->mantenimiento), ['accion' => 'anular'])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $this->mantenimiento->fresh()->anulado);
        $this->assertEquals(0, $this->ingresosActivos());
    }

    private function crearStock(array $extra = []): Stock
    {
        return Stock::create(array_merge([
            'codigo' => 'STK-'.uniqid(),
            'producto' => 'Producto Auditoría',
            'categoria' => 'Test',
            'subcategoria' => 'Sub',
            'cantidad' => 10,
            'precio_compra' => 1000,
            'utilidad' => 50,
            'precio_venta' => 2000,
            'precio_tecnico' => 1500,
            'active' => true,
        ], $extra));
    }

    private function vender(Stock $stock, int $pagado): Factura
    {
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::first()->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => $pagado,
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 2000]],
        ])->assertRedirect();

        return Factura::latest('id')->first();
    }

    /** Duplicar un pago de factura no debe contarse como pago de esa factura. */
    public function test_duplicar_movimiento_no_hereda_factura(): void
    {
        $factura = $this->vender($this->crearStock(), 1000);
        $mov = MovimientoCaja::where('factura_id', $factura->id)->first();

        $this->actingAs($this->admin)->post(route('caja.duplicate', $mov))->assertRedirect();

        $copia = MovimientoCaja::latest('id')->first();
        $this->assertNotEquals($mov->id, $copia->id);
        $this->assertNull($copia->factura_id);
        $this->assertNull($copia->abono_id);
        $this->assertEquals(1000, (float) $factura->fresh()->total_pagado);
    }

    /** Reactivar en caja el pago de una factura anulada volvería a cobrar el dinero. */
    public function test_no_se_reactiva_pago_de_factura_anulada(): void
    {
        $factura = $this->vender($this->crearStock(), 2000);
        $this->actingAs($this->admin)->post(route('inventario.facturas.anular', $factura))->assertRedirect();
        $mov = MovimientoCaja::where('factura_id', $factura->id)->first();
        $this->assertTrue($mov->anulado);

        $this->actingAs($this->admin)->post(route('caja.anular', $mov))->assertSessionHas('error');

        $this->assertTrue($mov->fresh()->anulado);
        $this->assertEquals(0, $this->ingresosActivos());
    }

    /** Un pago de orden no admite abonos desde caja (se cobraría dos veces). */
    public function test_no_se_abona_desde_caja_un_pago_de_orden(): void
    {
        $this->abonar(40000);
        $mov = MovimientoCaja::whereNotNull('abono_id')->first();

        $this->actingAs($this->admin)->post(route('caja.abonos.store', $mov), [
            'monto_abono' => 60000,
            'fecha' => Carbon::today()->toDateString(),
            'tipo_pago' => 'efectivo',
        ])->assertSessionHas('error');

        $this->assertEquals(40000, $this->ingresosActivos());
    }

    /** "#VT-1" no debe coincidir con "#VT-10". */
    public function test_referencia_exacta_no_mezcla_documentos(): void
    {
        $base = ['concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros ingresos'])->id, 'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'fecha' => Carbon::today(), 'estado' => 'activo', 'anulado' => false, 'persona' => 'X', 'user_id' => $this->admin->id];
        MovimientoCaja::create($base + ['monto' => 10, 'descripcion' => 'Cobro venta #VT-1']);
        MovimientoCaja::create($base + ['monto' => 20, 'descripcion' => 'Cobro venta #VT-10']);
        MovimientoCaja::create($base + ['monto' => 30, 'descripcion' => 'Abono parcial a #VT-1 (Ref)']);

        $this->assertEquals(40, (float) MovimientoCaja::referenciaExacta('#VT-1')->sum('monto'));
    }

    /** El costo de una orden no puede editarse por debajo de lo abonado. */
    public function test_costo_no_baja_de_lo_abonado(): void
    {
        $this->abonar(80000);
        $m = $this->mantenimiento->fresh();

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), [
            'fecha_entrada' => Carbon::parse($m->fecha_entrada)->toDateString(),
            'tipo' => $m->tipo, 'reparacion' => $m->reparacion, 'descripcion' => $m->descripcion,
            'costo' => 1000, 'estado' => 'pendiente', 'equipo_id' => $m->equipo_id, 'tecnico_id' => $m->tecnico_id,
        ])->assertSessionHasErrors('costo');

        $this->assertEquals(100000, (float) $m->fresh()->costo);
    }

    /** Duplicar una orden anulada crea una copia activa sin el valor de repuestos. */
    public function test_duplicar_orden_no_hereda_anulacion_ni_repuestos(): void
    {
        $stock = $this->crearStock();
        $this->mantenimiento->stocks()->attach($stock->id, ['cantidad' => 2, 'precio_unitario' => 10000]);
        $this->mantenimiento->update(['costo' => 120000, 'anulado' => true]);

        $this->actingAs($this->admin)->post(route('mantenimientos.duplicate', $this->mantenimiento))->assertRedirect();

        $copia = Mantenimiento::latest('id')->first();
        $this->assertFalse((bool) $copia->anulado);
        $this->assertEquals(100000, (float) $copia->costo);
    }

    /** Endpoints que antes respondían 500. */
    public function test_endpoints_que_antes_daban_error_500(): void
    {
        $this->actingAs($this->admin)->get(route('proveedores.index', ['locate' => 1]))->assertRedirect();

        $proveedor = Proveedor::create(['nombre_razon_social' => 'Prov', 'identificacion' => '900', 'tipo_entidad' => 'empresa']);
        $this->actingAs($this->admin)->post(route('stocks.store'), [
            'codigo' => 'STK-SIN-PRECIOS', 'producto' => 'Sin precios', 'categoria' => 'A', 'subcategoria' => 'B',
            'cantidad' => 1, 'proveedor_id' => $proveedor->id, 'precio_compra' => 0, 'utilidad' => 0,
        ])->assertRedirect(route('stocks.index'));
        $this->assertDatabaseHas('stocks', ['codigo' => 'STK-SIN-PRECIOS', 'precio_venta' => 0]);

        $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'PC', 'marca' => 'X', 'modelo' => 'Y', 'serie' => 'S-999', 'propietario_global' => 'Cliente:99999',
        ])->assertSessionHasErrors('propietario_global');
    }

    /** Un repuesto anulado no puede añadirse a una orden. */
    public function test_no_se_agrega_repuesto_anulado(): void
    {
        $stock = $this->crearStock(['active' => false]);

        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $this->mantenimiento), [
            'stock_id' => $stock->id, 'cantidad' => 1,
        ])->assertSessionHas('error');

        $this->assertEquals(10, $stock->fresh()->cantidad);
    }

    /** La contraseña de administrador no se puede adivinar por fuerza bruta. */
    public function test_password_admin_tiene_limite_de_intentos(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'tecnico']));
        $servicio = app(AnulacionService::class);

        for ($i = 0; $i < AnulacionService::MAX_INTENTOS_PASSWORD_ADMIN; $i++) {
            $this->assertFalse($servicio->adminPasswordValida('incorrecta'.$i));
        }

        $this->assertTrue($servicio->passwordAdminBloqueada());
    }

    /** La venta registra en caja el medio de pago real (antes siempre "efectivo"). */
    public function test_venta_registra_el_medio_de_pago_elegido(): void
    {
        $stock = $this->crearStock();
        $this->actingAs($this->admin)->post(route('inventario.venta.store'), [
            'facturable_global' => 'Cliente:'.Cliente::first()->id,
            'fecha' => Carbon::today()->toDateString(),
            'total_pagado' => 2000,
            'tipo_pago' => 'consignacion',
            'items' => [['stock_id' => $stock->id, 'cantidad' => 1, 'precio_unitario' => 2000]],
        ])->assertRedirect();

        $factura = Factura::latest('id')->first();
        $this->assertEquals('consignacion', MovimientoCaja::where('factura_id', $factura->id)->value('tipo_pago'));
    }

    /** Un técnico no puede ajustar existencias a mano sin la contraseña de un administrador. */
    public function test_tecnico_no_ajusta_stock_sin_password_admin(): void
    {
        $stock = $this->crearStock(['proveedor_id' => Proveedor::create(['nombre_razon_social' => 'P', 'identificacion' => '901', 'tipo_entidad' => 'empresa'])->id]);
        $tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->actingAs($tecnico)->put(route('stocks.update', $stock), [
            'codigo' => $stock->codigo, 'producto' => $stock->producto, 'categoria' => 'Test', 'subcategoria' => 'Sub',
            'cantidad' => 3, 'proveedor_id' => $stock->proveedor_id, 'precio_compra' => 1000, 'utilidad' => 50,
            'precio_venta' => 2000, 'precio_tecnico' => 1500,
        ])->assertSessionHasErrors('admin_password');

        $this->assertEquals(10, $stock->fresh()->cantidad);
    }

    /** El saldo inicial usa el último conteo real aunque el cierre más reciente no se haya contado. */
    public function test_saldo_inicial_usa_ultimo_conteo_real(): void
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Otros ingresos']);
        MovimientoCaja::create(['concepto_id' => $concepto->id, 'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo',
            'fecha' => '2026-10-01', 'monto' => 100, 'estado' => 'activo', 'anulado' => false, 'persona' => 'X', 'user_id' => $this->admin->id]);

        $cierre = ['total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0, 'consignacion' => 0, 'saldo_final' => 0, 'num_movimientos' => 0, 'user_id' => $this->admin->id];
        CierreCaja::create($cierre + ['fecha' => '2026-10-01', 'efectivo_real_contado' => 90]); // faltaron 10
        CierreCaja::create($cierre + ['fecha' => '2026-10-02']);                                 // sin conteo

        $this->assertEquals(90.0, CierreCaja::saldoInicialEfectivo('2026-10-03'));
    }

    /** Un nombre compartido por dos usuarios no inicia sesión; el correo siempre tiene prioridad. */
    public function test_login_por_nombre_ambiguo_no_suplanta(): void
    {
        $admin = User::factory()->create(['name' => 'Gerente', 'email' => 'gerente@x.com', 'password' => bcrypt('Clave-Admin-1'), 'role' => 'admin']);
        User::factory()->create(['name' => 'Gerente', 'email' => 'otro@x.com', 'password' => bcrypt('Clave-Otro-1'), 'role' => 'tecnico']);

        $this->post('/login', ['email' => 'Gerente', 'password' => 'Clave-Otro-1']);
        $this->assertGuest();

        $this->post('/login', ['email' => 'gerente@x.com', 'password' => 'Clave-Admin-1']);
        $this->assertAuthenticatedAs($admin);
    }

    /** Solo se reabre el último día cerrado (borrar uno intermedio alteraba los arqueos posteriores). */
    public function test_no_se_borra_un_cierre_intermedio(): void
    {
        $cierre = ['total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0, 'consignacion' => 0, 'saldo_final' => 0, 'num_movimientos' => 0, 'user_id' => $this->admin->id];
        $intermedio = CierreCaja::create($cierre + ['fecha' => '2026-10-01']);
        CierreCaja::create($cierre + ['fecha' => '2026-10-02']);

        $this->actingAs($this->admin)->delete(route('cierre.destroy', $intermedio))->assertSessionHas('error');
        $this->assertDatabaseHas('cierre_cajas', ['id' => $intermedio->id]);
    }

    /** No se desactiva un técnico ni un equipo con órdenes pendientes. */
    public function test_no_se_desactiva_catalogo_con_ordenes_pendientes(): void
    {
        $m = $this->mantenimiento;
        $this->actingAs($this->admin)->post(route('tecnicos.anular', $m->tecnico_id))->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('equipos.anular', $m->equipo_id))->assertSessionHas('error');

        $this->assertTrue((bool) Tecnico::find($m->tecnico_id)->active);
        $this->assertTrue((bool) Equipo::find($m->equipo_id)->active);
    }

    /** La numeración se sincroniza con el mayor número existente aunque la secuencia vaya atrás. */
    public function test_numeracion_no_repite_numeros_existentes(): void
    {
        DB::table('secuencias_documento')->updateOrInsert(['prefijo' => 'ORD-'], ['valor' => 1]);
        Mantenimiento::factory()->create(['id_orden' => 'ORD-40', 'equipo_id' => $this->mantenimiento->equipo_id, 'tecnico_id' => $this->mantenimiento->tecnico_id, 'user_id' => $this->admin->id]);

        $siguiente = DB::transaction(fn () => app(OrdenService::class)->siguiente('ORD-', Mantenimiento::class));

        $this->assertSame('ORD-41', $siguiente);
    }

    /** Los PDF se numeran sin PHP embebido en dompdf (enable_php apagado). */
    public function test_pdf_numera_paginas_sin_php_embebido(): void
    {
        $this->assertFalse((bool) config('dompdf.options.enable_php'));
        Mantenimiento::factory()->count(60)->create([
            'equipo_id' => $this->mantenimiento->equipo_id, 'tecnico_id' => $this->mantenimiento->tecnico_id, 'user_id' => $this->admin->id,
        ]);

        $pdf = $this->actingAs($this->admin)->get(route('mantenimientos.reportes', ['export' => 'pdf']))->assertOk()->getContent();

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);
        $texto = implode('', array_map(fn ($s) => @gzuncompress($s) ?: '', $m[1]));
        preg_match_all('/gina (\d+) de (\d+)/', $texto, $numeros);

        $this->assertNotEmpty($numeros[0], 'El PDF debe incluir "Página X de Y"');
        $this->assertSame('1', $numeros[1][0]);
        $this->assertSame(end($numeros[2]), end($numeros[1]), 'La última página debe ser "N de N"');
    }

    /** Un texto que empieza por "=" se exporta como texto, no como fórmula. */
    public function test_exportacion_no_ejecuta_formulas(): void
    {
        $hoja = new Spreadsheet;
        $celda = $hoja->getActiveSheet()->getCell('A1');
        (new SafeValueBinder)->bindValue($celda, '=HYPERLINK("https://x","clic")');

        $this->assertSame(DataType::TYPE_STRING, $celda->getDataType());
    }
}
