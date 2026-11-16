<?php

namespace Tests\Feature;

use App\Models\Abono;
use App\Models\ConceptoCaja;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Regresión de hallazgos de backend, base de datos y documentos de la auditoría integral.
 */
class AuditoriaIntegralCorreccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
    }

    /** B-02: quitar un repuesto no puede dejar el costo de la orden por debajo de lo abonado. */
    public function test_b02_quitar_repuesto_no_oculta_un_sobrepago(): void
    {
        $stock = Stock::create([
            'codigo' => 'R-1', 'producto' => 'Pantalla', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => 5, 'precio_compra' => 30000, 'utilidad' => 50, 'precio_venta' => 50000, 'active' => true,
        ]);
        $orden = Mantenimiento::factory()->create(['costo' => 50000, 'anulado' => false, 'estado' => 'pendiente']);

        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden), ['stock_id' => $stock->id, 'cantidad' => 1]);
        // El precio del repuesto depende del tipo de cliente (normal o técnico)
        $costo = (float) $orden->fresh()->costo;
        $this->assertGreaterThan(50000, $costo);

        $this->post(route('abonos.store', $orden), ['monto' => $costo, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo']);
        $this->assertEquals($costo, (float) $orden->fresh()->total_abonado);

        $this->delete(route('mantenimientos.stocks.destroy', [$orden, $stock->id]))->assertSessionHas('error');

        $this->assertEquals($costo, (float) $orden->fresh()->costo, 'El costo no baja por debajo de lo abonado');
        $this->assertSame(1, $orden->fresh()->stocks()->count());
        $this->assertSame(4, $stock->fresh()->cantidad);
    }

    /** B-12: el último administrador activo no puede quitarse el rol. */
    public function test_b12_no_queda_el_sistema_sin_administrador(): void
    {
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'tecnico',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $this->admin->fresh()->role);

        // Con otro administrador activo, sí puede cambiarse
        User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'tecnico',
        ])->assertSessionHasNoErrors();
        $this->assertSame('tecnico', $this->admin->fresh()->role);
    }

    /** X-01: la exportación del informe mensual incluye todos los movimientos, no solo la página. */
    public function test_x01_exportacion_del_informe_incluye_todos_los_movimientos(): void
    {
        Excel::fake();
        $concepto = ConceptoCaja::create(['nombre' => 'Ventas']);
        foreach (range(1, 14) as $i) {
            MovimientoCaja::create([
                'persona' => "P{$i}", 'fecha' => now()->toDateString(), 'concepto_id' => $concepto->id,
                'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 1000,
                'estado' => 'activo', 'anulado' => false, 'user_id' => $this->admin->id,
            ]);
        }

        $this->actingAs($this->admin)->get(route('reportes.index', ['export' => 'excel']));

        // El nombre lleva la hora al segundo: se compara con una expresión regular para que la
        // prueba no falle si el reloj cambia de segundo entre la petición y la comprobación.
        Excel::matchByRegex();
        Excel::assertDownloaded('/^Reporte_Financiero_\d{4}-\d{2}-\d{2}_\d{6}\.xlsx$/', function ($export) {
            return $export->collection()->count() === 14;
        });
    }

    /** X-03: la factura de la orden no presenta abonos anulados como pagos. */
    public function test_x03_factura_de_orden_no_muestra_abonos_anulados(): void
    {
        $orden = Mantenimiento::factory()->create(['costo' => 100000, 'anulado' => false, 'estado' => 'pendiente']);
        $this->actingAs($this->admin)->post(route('abonos.store', $orden), ['monto' => 20000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo', 'descripcion' => 'Abono vigente']);
        $this->post(route('abonos.store', $orden), ['monto' => 1000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo', 'descripcion' => 'Abono anulado X']);
        Abono::where('monto', 1000)->update(['anulado' => true]);

        // B-01: solo se factura una orden terminada.
        $orden->update(['estado' => 'terminado', 'fecha_salida' => now()]);
        $this->get(route('mantenimientos.factura', $orden))->assertOk();

        // El PDF va comprimido: se verifica el HTML que recibe dompdf
        $datos = fn () => ['mantenimiento' => $orden->fresh()->load(['equipo.cliente', 'tecnico', 'user', 'abonos', 'stocks'])];

        $a4 = view('mantenimientos.factura', $datos())->render();
        $this->assertStringContainsString('Abono vigente', $a4);
        $this->assertStringNotContainsString('Abono anulado X', $a4);

        // El ticket POS lista los abonos por monto
        $pos = view('mantenimientos.factura_pos', $datos())->render();
        $this->assertStringContainsString('20.000', $pos);
        $this->assertStringNotContainsString('$ 1.000', $pos);
    }
}
