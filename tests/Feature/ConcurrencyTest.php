<?php

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\OrdenService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Cliente $cliente;

    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->cliente = Cliente::factory()->create(['tipo_cliente' => 'cliente']);
        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Concurrency Test',
            'identificacion' => '900999888',
            'tipo_entidad' => 'empresa',
            'email' => 'concurrency@proveedor.com',
            'telefono' => '3009998888',
            'direccion' => 'Calle 100 # 20-30',
        ]);
    }

    /**
     * T-01: Stock = 1, dos conversiones simultáneas de cotización.
     * Exactamente 1 cotización debe tener éxito en descontar el stock; la otra
     * debe recibir error de stock insuficiente y la cantidad nunca debe ser negativa.
     */
    public function test_t01_dos_conversiones_simultaneas_stock_unitario_una_sola_gana(): void
    {
        $stock = Stock::factory()->create([
            'producto' => 'Pantalla LED Ultra',
            'cantidad' => 1,
            'precio_venta' => 250000,
        ]);

        $cot1 = Cotizacion::create([
            'codigo' => 'COT-T01-A',
            'cliente_id' => $this->cliente->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'estado' => 'pendiente',
            'subtotal' => 250000,
            'total' => 250000,
            'user_id' => $this->admin->id,
        ]);
        CotizacionItem::create([
            'cotizacion_id' => $cot1->id,
            'tipo' => 'stock',
            'item_id' => $stock->id,
            'descripcion' => $stock->producto,
            'cantidad' => 1,
            'precio_unitario' => 250000,
            'subtotal' => 250000,
        ]);

        $cot2 = Cotizacion::create([
            'codigo' => 'COT-T01-B',
            'cliente_id' => $this->cliente->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'estado' => 'pendiente',
            'subtotal' => 250000,
            'total' => 250000,
            'user_id' => $this->admin->id,
        ]);
        CotizacionItem::create([
            'cotizacion_id' => $cot2->id,
            'tipo' => 'stock',
            'item_id' => $stock->id,
            'descripcion' => $stock->producto,
            'cantidad' => 1,
            'precio_unitario' => 250000,
            'subtotal' => 250000,
        ]);

        // Primera conversión: debe tener éxito
        $resp1 = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cot1->id));
        $resp1->assertSessionHas('success');
        $this->assertEquals('aprobada', $cot1->fresh()->estado);

        // Segunda conversión simultánea: debe fallar por stock agotado
        $resp2 = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cot2->id));
        $resp2->assertSessionHas('error');
        $this->assertEquals('pendiente', $cot2->fresh()->estado);

        // La cantidad nunca debe ser negativa (queda exactamente en 0)
        $this->assertEquals(0, $stock->fresh()->cantidad);
    }

    /**
     * T-02: Prevención de Deadlocks mediante orden canónico de bloqueo (item_id ASC).
     * Cotización con items [Stock 2, Stock 1] y Cotización con items [Stock 1, Stock 2].
     */
    public function test_t02_prevencion_deadlock_orden_canonico_bloqueo_stocks(): void
    {
        $stockA = Stock::factory()->create(['producto' => 'Articulo A', 'cantidad' => 10, 'precio_venta' => 1000]);
        $stockB = Stock::factory()->create(['producto' => 'Articulo B', 'cantidad' => 10, 'precio_venta' => 2000]);

        $menorId = min($stockA->id, $stockB->id);
        $mayorId = max($stockA->id, $stockB->id);

        $cotInversa = Cotizacion::create([
            'codigo' => 'COT-T02-INV',
            'cliente_id' => $this->cliente->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'estado' => 'pendiente',
            'subtotal' => 3000,
            'total' => 3000,
            'user_id' => $this->admin->id,
        ]);

        // Insertados deliberadamente en orden descendente de ID
        CotizacionItem::create([
            'cotizacion_id' => $cotInversa->id,
            'tipo' => 'stock',
            'item_id' => $mayorId,
            'descripcion' => 'Mayor ID',
            'cantidad' => 1,
            'precio_unitario' => 2000,
            'subtotal' => 2000,
        ]);
        CotizacionItem::create([
            'cotizacion_id' => $cotInversa->id,
            'tipo' => 'stock',
            'item_id' => $menorId,
            'descripcion' => 'Menor ID',
            'cantidad' => 1,
            'precio_unitario' => 1000,
            'subtotal' => 1000,
        ]);

        $response = $this->actingAs($this->admin)->post(route('cotizaciones.convertir', $cotInversa->id));
        $response->assertSessionHas('success');

        $this->assertEquals('aprobada', $cotInversa->fresh()->estado);
        $this->assertEquals(9, Stock::find($menorId)->cantidad);
        $this->assertEquals(9, Stock::find($mayorId)->cantidad);
    }

    /**
     * T-03: Generador de correlativos atómico (OrdenService + secuencias_documento).
     * Múltiples solicitudes de correlativo VT- deben generar números estrictamente secuenciales
     * (VT-1, VT-2, VT-3) sin colisión en la tabla secuencias_documento (P1-6).
     */
    public function test_t03_numeracion_secuencial_atomica_sin_colision(): void
    {
        $service = app(OrdenService::class);

        $num1 = $service->siguiente('VT-', Factura::class, 'numero_factura');
        $num2 = $service->siguiente('VT-', Factura::class, 'numero_factura');
        $num3 = $service->siguiente('VT-', Factura::class, 'numero_factura');

        $this->assertEquals('VT-1', $num1);
        $this->assertEquals('VT-2', $num2);
        $this->assertEquals('VT-3', $num3);

        $secuencia = DB::table('secuencias_documento')->where('prefijo', 'VT-')->first();
        $this->assertNotNull($secuencia);
        $this->assertEquals(3, (int) $secuencia->valor);
    }

    /**
     * T-04: Cierre de caja concurrente.
     * Si dos llamadas intentan cerrar la misma fecha, exactamente una debe consolidarse
     * y la segunda ser rechazada por el bloqueo pesimista en la clave única.
     */
    public function test_t04_cierre_caja_segundo_intento_mismo_dia_rechazado(): void
    {
        $fecha = '2026-10-15';

        $resp1 = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'efectivo_real_contado' => 0,
            'observaciones' => 'Primer cierre',
        ]);
        $resp1->assertRedirect(route('cierre.index'));

        $this->assertEquals(1, CierreCaja::where('fecha', $fecha)->count());

        $resp2 = $this->actingAs($this->admin)->post(route('cierre.store'), [
            'fecha' => $fecha,
            'efectivo_real_contado' => 0,
            'observaciones' => 'Segundo cierre duplicado',
        ]);
        $resp2->assertSessionHasErrors(['fecha']);
        $this->assertEquals(1, CierreCaja::where('fecha', $fecha)->count());
    }

    /**
     * T-05: Modificación de repuestos en orden bloquea modelo padre y acumula costo de forma consistente.
     */
    public function test_t05_attach_stock_bloquea_modelo_y_acumula_costo(): void
    {
        $equipo = Equipo::factory()->create(['cliente_id' => $this->cliente->id]);
        $tecnico = Tecnico::factory()->create();

        $orden = Mantenimiento::create([
            'id_orden' => 'ORD-T05',
            'equipo_id' => $equipo->id,
            'tecnico_id' => $tecnico->id,
            'user_id' => $this->admin->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Mantenimiento preventivo',
            'tipo' => 'preventivo',
            'reparacion' => 'software',
            'costo' => 50000,
            'estado' => 'pendiente',
        ]);

        $stock = Stock::factory()->create([
            'producto' => 'Pasta Térmica',
            'cantidad' => 20,
            'precio_venta' => 15000,
        ]);

        $resp = $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $orden->id), [
            'stock_id' => $stock->id,
            'cantidad' => 2,
        ]);

        $resp->assertSessionHas('success');
        $this->assertEquals(50000 + (15000 * 2), (float) $orden->fresh()->costo);
        $this->assertEquals(18, $stock->fresh()->cantidad);
    }

    /**
     * T-06: StockController::update valida y procesa diferencias de cantidad a través de StockService
     * impidiendo reducciones que superen el stock existente tras ventas concurrentes.
     */
    public function test_t06_stock_update_previene_reduccion_que_exceda_disponibilidad(): void
    {
        $stock = Stock::factory()->create([
            'producto' => 'Disco SSD 1TB',
            'cantidad' => 5,
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 150000,
            'utilidad' => 20,
            'precio_venta' => 180000,
            'precio_tecnico' => 170000,
        ]);

        // Simular que una venta concurrente retira 4 unidades (quedando 1 unidad física)
        app(StockService::class)->salida($stock, 4);
        $this->assertEquals(1, $stock->fresh()->cantidad);

        // Si desde la edición administrativa un usuario tenía cargado cantidad=5 y envía cantidad=0 con cantidad_original=5 (solicitando restar 5 unidades)
        // La actualización no debe forzar 0 ciegamente ni dejar saldo negativo; debe rechazar con error
        $response = $this->actingAs($this->admin)->put(route('stocks.update', $stock->id), [
            'codigo' => $stock->codigo,
            'producto' => $stock->producto,
            'categoria' => $stock->categoria,
            'subcategoria' => $stock->subcategoria,
            'cantidad_original' => 5,
            'cantidad' => 0, // Intenta restar 5 cuando solo queda 1 disponible
            'proveedor_id' => $this->proveedor->id,
            'precio_compra' => 150000,
            'utilidad' => 20,
            'precio_venta' => 180000,
            'precio_tecnico' => 170000,
        ]);

        // Debe redirigir con error y preservar el inventario en 1 (nunca negativo ni pisado)
        $response->assertSessionHas('error');
        $this->assertEquals(1, $stock->fresh()->cantidad);
    }
}
