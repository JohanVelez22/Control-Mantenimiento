<?php

namespace Tests\Feature;

use App\Models\CategoriaStock;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * F-02: La columna huérfana 'activo' en 'proveedores' fue removida; se usa 'active'.
     */
    public function test_proveedores_usa_columna_active_y_no_activo(): void
    {
        $this->assertFalse(Schema::hasColumn('proveedores', 'activo'), 'La columna huérfana activo no debe existir');
        $this->assertTrue(Schema::hasColumn('proveedores', 'active'), 'La columna canónica active debe existir');

        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'tipo_identificacion' => 'nit',
            'identificacion' => '901234567-8',
            'nombre_razon_social' => 'Distribuciones Tech SAS',
            'telefono' => '3101234567',
            'active' => true,
        ]);

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre_razon_social' => 'Distribuciones Tech SAS',
            'active' => 1,
        ]);
    }

    /**
     * F-04 / ANEXO A: Restricción de unicidad en mantenimiento_stock.
     */
    public function test_mantenimiento_stock_no_permite_duplicados_exactos(): void
    {
        $mantenimiento = Mantenimiento::factory()->create();
        $stock = Stock::create([
            'codigo' => 'DISC-1TB',
            'producto' => 'Disco SSD 1TB',
            'categoria' => 'Discos',
            'cantidad' => 10,
            'precio_compra' => 120000,
            'precio_venta' => 200000,
            'active' => true,
        ]);

        DB::table('mantenimiento_stock')->insert([
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $stock->id,
            'cantidad' => 1,
            'precio_unitario' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('mantenimiento_stock')->insert([
            'mantenimiento_id' => $mantenimiento->id,
            'stock_id' => $stock->id,
            'cantidad' => 2,
            'precio_unitario' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * F-05 / ANEXO A: Restricción de unicidad en categoria_stocks (nombre, tipo).
     */
    public function test_categoria_stocks_no_permite_duplicados_nombre_tipo(): void
    {
        CategoriaStock::create([
            'nombre' => 'Periféricos',
            'tipo' => 'categoria',
        ]);

        $this->expectException(QueryException::class);

        CategoriaStock::create([
            'nombre' => 'Periféricos',
            'tipo' => 'categoria',
        ]);
    }

    /**
     * F-03 / F-45: Configuracion::getGlobal() almacena en caché y se invalida al guardar.
     */
    public function test_configuracion_get_global_cachea_y_se_invalida_al_modificar(): void
    {
        Configuracion::clearGlobalCache();

        $config = Configuracion::getGlobal();
        $this->assertNotNull($config);
        $this->assertTrue(Cache::has('configuracion_global'));

        // Modificar mediante el modelo para disparar hooks de Eloquent
        $config->update(['nombre' => 'Tecni-Systemas Hardened']);

        // El hook static::saved debe haber invalidado la caché
        $this->assertFalse(Cache::has('configuracion_global'), 'La caché debe invalidarse al guardar la configuración');

        $nuevoConfig = Configuracion::getGlobal();
        $this->assertEquals('Tecni-Systemas Hardened', $nuevoConfig->nombre);
    }

    /**
     * F-16 / F-18: Consultas de auditoría e historial en eventos y cotizaciones.
     */
    public function test_consultas_optimizadas_en_eventos_y_cotizaciones(): void
    {
        $mantenimiento = Mantenimiento::factory()->create();

        $historial = Evento::where('modelo_tipo', Mantenimiento::class)
            ->where('modelo_id', $mantenimiento->id)
            ->latest('created_at')
            ->get();

        $this->assertNotEmpty($historial);

        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-2026-0001',
            'cliente_id' => Equipo::factory()->create()->cliente_id,
            'user_id' => $this->admin->id,
            'fecha' => now()->toDateString(),
            'validez_dias' => 15,
            'total' => 119000,
            'estado' => 'pendiente',
        ]);

        $cotizacionesPendientes = Cotizacion::where('fecha', now()->toDateString())
            ->where('estado', 'pendiente')
            ->get();

        $this->assertCount(1, $cotizacionesPendientes);
        $this->assertEquals($cotizacion->id, $cotizacionesPendientes->first()->id);
    }

    /**
     * F-19: Reporte diario ejecuta agregación agrupada de facturas correctamente.
     */
    public function test_reporte_financiero_ejecuta_agregacion_correctamente(): void
    {
        $hoy = now()->toDateString();

        Factura::create([
            'numero_factura' => 'VT-1001',
            'cliente_id' => Equipo::factory()->create()->cliente_id,
            'user_id' => $this->admin->id,
            'tipo_movimiento' => 'venta',
            'fecha' => $hoy,
            'subtotal' => 200000,
            'total_documento' => 200000,
            'total_pagado' => 150000,
            'saldo_pendiente' => 50000,
            'estado' => 'pendiente_pago',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reportes.financiero.diario', ['fecha' => $hoy]));
        $response->assertStatus(200);
        $resumen = $response->viewData('resumen');
        $this->assertEquals(200000.0, $resumen['total_ventas']);
        $this->assertEquals(50000.0, $resumen['saldo_pendiente_venta']);
    }
}
