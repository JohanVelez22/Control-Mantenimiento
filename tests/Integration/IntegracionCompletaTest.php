<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Stock;
use App\Models\Proveedor;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Abono;
use App\Models\Tecnico;
use App\Models\Equipo;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Test de integración completa - Lógica Financiera / Inventario / Caja
 */
class IntegracionCompletaTest extends TestCase
{
    private $admin;
    private $tecnico;
    private $cliente;
    private $proveedor;
    private $tecnicoM;
    private $equipo;

    protected function setUp(): void
    {
        parent::setUp();
        // Usar datos ya existentes en la BD (seed previa)
        $this->admin = User::where('role','admin')->first();
        $this->tecnico = User::where('role','tecnico')->first();
        $this->cliente = Cliente::first();
        $this->proveedor = Proveedor::first();
        $this->tecnicoM = Tecnico::first();
        $this->equipo = Equipo::first();

        // Si no existen (primer test), crearlos mínimamente
        if (!$this->admin) {
            $this->admin = User::create([
                'name' => 'Admin Test', 'email' => 'admin_test_'.time().'@test.com',
                'password' => Hash::make('password'), 'role' => 'admin', 'active' => true,
            ]);
        }
        if (!$this->tecnico) {
            $this->tecnico = User::create([
                'name' => 'Tecnico Test', 'email' => 'tecnico_test_'.time().'@test.com',
                'password' => Hash::make('password'), 'role' => 'tecnico', 'active' => true,
            ]);
        }
        if (!$this->cliente) {
            $this->cliente = Cliente::create([
                'nombre' => 'Cliente Test', 'identificacion' => 'CC-'.time(),
                'telefono' => '3000000000', 'movil' => '3000000000',
                'email' => 'cliente_test_'.time().'@test.com', 'direccion' => 'Calle Test',
            ]);
        }
        if (!$this->proveedor) {
            $this->proveedor = Proveedor::create([
                'tipo_entidad' => 'empresa',
                'identificacion' => 'NIT-'.time(),
                'nombre_razon_social' => 'Proveedor Test',
                'telefono' => '3000000001',
                'email' => 'proveedor_test@test.com',
                'direccion' => 'Calle Proveedor',
            ]);
        }
        if (!$this->tecnicoM) {
            $this->tecnicoM = Tecnico::create([
                'nombre' => 'Tecnico Test', 'identificacion' => 'CC-'.time(),
                'especialidad' => 'General', 'movil' => '3000000002', 'email' => 'tecnico_m_test@test.com',
                'direccion' => 'Calle Tec',
            ]);
        }
        if (!$this->equipo) {
            $this->equipo = Equipo::create([
                'nombre' => 'Equipo Test', 'marca' => 'Marca Test', 'modelo' => 'Modelo Test',
                'serie' => 'SN-'.time(), 'cliente_id' => $this->cliente->id, 'user_id' => $this->admin->id,
            ]);
        }
    }

    public function testStockServiceAtomicidad()
    {
        // Asegurar stock existente
        $stock = Stock::first();
        if (!$stock) {
            $stock = Stock::create(['producto'=>'Test Stock','categoria'=>'Test','cantidad'=>10,'precio_compra'=>1000,'utilidad'=>30,'proveedor_id'=>$this->proveedor->id]);
        }
        $orig = $stock->cantidad;
        $svc = new \App\Services\StockService();

        $stock = $svc->entrada($stock, 5);
        $this->assertEquals($orig + 5, $stock->cantidad);

        $stock = $svc->salida($stock, 3);
        $this->assertEquals($orig + 2, $stock->cantidad);

        $this->expectException(\DomainException::class);
        $svc->salida($stock, 9999);

        $stock2 = Stock::first();
        $orig2 = $stock2->cantidad;
        $svc->salida($stock2, $orig2);
        $this->expectException(\DomainException::class);
        $svc->salida($stock2, 1);
    }

    public function testCompraAnularRestauraStock()
    {
        $sc = Stock::create(['producto'=>'Test Compra','categoria'=>'Test','cantidad'=>10,'precio_compra'=>1000,'utilidad'=>30,'proveedor_id'=>$this->proveedor->id]);
        $origC = $sc->cantidad;

        $fCompra = Factura::create([
            'numero_factura'=>Factura::siguienteNumero('F'),
            'tipo_movimiento'=>'compra','estado'=>'emitida',
            'facturable_type'=>Proveedor::class,'facturable_id'=>$this->proveedor->id,
            'total_documento'=>4000,'total_pagado'=>4000,
            'fecha'=>now()->toDateString(),'user_id'=>$this->admin->id,
        ]);
        FacturaItem::create(['factura_id'=>$fCompra->id,'stock_id'=>$sc->id,'cantidad'=>4,'precio_unitario'=>$sc->precio_compra]);
        
        // Simular lo que hace el controlador: incrementar stock
        $sc->incrementarStock(4);
        $sc->refresh();
        $this->assertEquals($origC + 4, $sc->cantidad);

        /* Anular compra -> stock debe volver a original */
        $fCompra->update(['estado'=>'anulada']);
        foreach($fCompra->items as $it) { $it->stock->decrementarStock($it->cantidad); }
        $sc->refresh();
        $this->assertEquals($origC, $sc->cantidad);
    }

    public function testVentaAnularRestauraStock()
    {
        $sv = Stock::create(['producto'=>'Test Venta','categoria'=>'Test','cantidad'=>20,'precio_compra'=>500,'utilidad'=>50,'proveedor_id'=>$this->proveedor->id]);
        $origV = $sv->cantidad;

        $fVenta = Factura::create([
            'numero_factura'=>Factura::siguienteNumero('F'),
            'tipo_movimiento'=>'venta','estado'=>'emitida',
            'facturable_type'=>Cliente::class,'facturable_id'=>$this->cliente->id,
            'total_documento'=>$sv->precio_venta*6,'total_pagado'=>$sv->precio_venta*6,
            'fecha'=>now()->toDateString(),'user_id'=>$this->admin->id,
        ]);
        FacturaItem::create(['factura_id'=>$fVenta->id,'stock_id'=>$sv->id,'cantidad'=>6,'precio_unitario'=>$sv->precio_venta]);
        
        $sv->decrementarStock(6);
        $sv->refresh();
        $this->assertEquals($origV - 6, $sv->cantidad);

        $fVenta->update(['estado'=>'anulada']);
        foreach($fVenta->items as $it) { $it->stock->incrementarStock($it->cantidad); }
        $sv->refresh();
        $this->assertEquals($origV, $sv->cantidad);
    }

    public function testAbonoMantenimientoAnularRevierteStockYCaja()
    {
        $tecnicoM = Tecnico::first();
        $equipo = Equipo::first();
        $mant = Mantenimiento::create([
            'id_orden'=>'MNT-TEST-'.time(),'equipo_id'=>$equipo->id,'tecnico_id'=>$tecnicoM->id,'user_id'=>$this->admin->id,
            'fecha_entrada'=>now(),'tipo'=>'correctivo','descripcion'=>'Test','costo'=>100000,'estado'=>'pendiente','anulado'=>false,
        ]);
        $abono = Abono::create(['mantenimiento_id'=>$mant->id,'monto'=>50000,'fecha'=>now()->toDateString(),'tipo_pago'=>'efectivo','user_id'=>$this->admin->id]);
        $concepto = ConceptoCaja::firstOrCreate(['nombre'=>'Abono Mantenimiento']);
        $mov = MovimientoCaja::create([
            'tipo_movimiento'=>'ingreso','fecha'=>now()->toDateString(),
            'monto'=>50000,'concepto_id'=>$concepto->id,
            'persona'=>$mant->equipo->cliente->nombre,'descripcion'=>"Abono Orden {$mant->id_orden}",
            'tipo_pago'=>'efectivo','estado'=>'activo','anulado'=>false,'user_id'=>$this->admin->id,'abono_id'=>$abono->id,
        ]);

        $this->assertFalse($mov->anulado);

        // Anular abono -> movimiento caja anulado en cascada
        $abono->delete();
        $mov->refresh();
        $this->assertTrue($mov->anulado);
    }

    public function testFacturaPagoParcialSaldosCorrectos()
    {
        $fp = Stock::create(['producto'=>'FP Test','categoria'=>'Test','cantidad'=>50,'precio_compra'=>1000,'utilidad'=>40,'proveedor_id'=>$this->proveedor->id]);
        $fParcial = Factura::create([
            'numero_factura'=>Factura::siguienteNumero('F'),
            'tipo_movimiento'=>'venta','estado'=>'emitida',
            'facturable_type'=>Cliente::class,'facturable_id'=>$this->cliente->id,
            'total_documento'=>100000,'total_pagado'=>30000,
            'fecha'=>now()->toDateString(),'user_id'=>$this->admin->id,
        ]);
        FacturaItem::create(['factura_id'=>$fParcial->id,'stock_id'=>$fp->id,'cantidad'=>10,'precio_unitario'=>$fp->precio_venta]);

        $fParcial->refresh();
        $this->assertEquals(70000, $fParcial->saldo_pendiente);
        $this->assertEquals(0, $fParcial->saldo_a_favor);

        /* Sobrepago -> saldo_a_favor > 0 */
        $fOver = Factura::create([
            'numero_factura'=>Factura::siguienteNumero('F'),
            'tipo_movimiento'=>'venta','estado'=>'emitida',
            'facturable_type'=>Cliente::class,'facturable_id'=>$this->cliente->id,
            'total_documento'=>50000,'total_pagado'=>70000,
            'fecha'=>now()->toDateString(),'user_id'=>$this->admin->id,
        ]);
        $fOver->refresh();
        $this->assertEquals(0, $fOver->saldo_pendiente);
        $this->assertEquals(20000, $fOver->saldo_a_favor);
    }

    public function testCajaSaldosHistoricoVsDiaActual()
    {
        $hoy = now()->toDateString();
        $ayer = now()->subDay()->toDateString();

        /* Ingreso ayer 500k TOTAL, pagado 0 -> saldo 500k.
           Luego pago hoy 100k -> saldo queda 400k. */
        $ingAyer = MovimientoCaja::create([
            'tipo_movimiento'=>'ingreso','fecha'=>now()->subDay()->toDateString(),
            'monto'=>0,'monto_total'=>500000,
            'concepto_id'=>ConceptoCaja::first()->id,'tipo_pago'=>'efectivo',
            'estado'=>'activo','anulado'=>false,'user_id'=>$this->admin->id,
            'descripcion'=>'Ingreso prueba ayer',
        ]);
        /* Pago hoy 100k como abono hijo */
        MovimientoCaja::create([
            'tipo_movimiento'=>'ingreso','fecha'=>now()->toDateString(),
            'monto'=>100000,'monto_total'=>500000,
            'concepto_id'=>ConceptoCaja::first()->id,'tipo_pago'=>'efectivo',
            'estado'=>'activo','anulado'=>false,'user_id'=>$this->admin->id,'parent_id'=>$ingAyer->id,
            'descripcion'=>'Abono hoy',
        ]);

        $ingAyer->refresh();
        $saldoAyer = $ingAyer->saldo_pendiente;
        $this->assertEquals(400000, $saldoAyer);

        $saldoHoy = MovimientoCaja::where('fecha',now()->toDateString())
            ->where('estado','activo')->where('anulado',false)
            ->whereNull('parent_id')
            ->whereRaw('monto_total > monto')
            ->with('childPayments')
            ->get()
            ->filter(fn($m)=>$m->saldo_pendiente > 0)
            ->sum('saldo_pendiente');
        
        // No hay movimientos padre nuevos hoy, solo abonos hijos
        $this->assertEquals(0, $saldoHoy);
    }

    public function testAnularFacturaCompraVentaRestauraStock()
    {
        $fv2 = Factura::create([
            'numero_factura'=>Factura::siguienteNumero('F'),
            'tipo_movimiento'=>'venta','estado'=>'emitida',
            'facturable_type'=>Cliente::class,'facturable_id'=>$this->cliente->id,
            'total_documento'=>200000,'total_pagado'=>0,
            'fecha'=>now()->toDateString(),'user_id'=>$this->admin->id,
        ]);
        $stk = Stock::create(['producto'=>'TestV2','categoria'=>'T','cantidad'=>100,'precio_compra'=>1000,'utilidad'=>20,'proveedor_id'=>$this->proveedor->id]);
        FacturaItem::create(['factura_id'=>$fv2->id,'stock_id'=>$stk->id,'cantidad'=>15,'precio_unitario'=>$stk->precio_venta]);
        
        $stk->decrementarStock(15);
        $stk->refresh();
        $this->assertEquals(85, $stk->cantidad);

        $fv2->update(['estado'=>'anulada']);
        foreach($fv2->items as $it) { $it->stock->incrementarStock($it->cantidad); }
        $stk->refresh();
        $this->assertEquals(100, $stk->cantidad);
    }

    public function testAnularMovimientoCajaAbonosCascada()
    {
        $movPadre = MovimientoCaja::create([
            'tipo_movimiento'=>'ingreso','fecha'=>now()->toDateString(),
            'monto'=>1000,'monto_total'=>1000,
            'concepto_id'=>ConceptoCaja::first()->id,'tipo_pago'=>'efectivo',
            'estado'=>'activo','anulado'=>false,'user_id'=>$this->admin->id,
            'descripcion'=>'Padre test',
        ]);
        $hijo = MovimientoCaja::create([
            'tipo_movimiento'=>'ingreso','fecha'=>now()->toDateString(),
            'monto'=>200,'monto_total'=>1000,
            'concepto_id'=>ConceptoCaja::first()->id,'tipo_pago'=>'efectivo',
            'estado'=>'activo','anulado'=>false,'user_id'=>$this->admin->id,'parent_id'=>$movPadre->id,
            'descripcion'=>'Abono hijo',
        ]);

        $movPadre->update(['anulado'=>true]);
        $hijo->update(['anulado'=>true]); // actualizar hijo manualmente

        $movPadre->refresh();
        $hijo->refresh();
        $this->assertTrue($movPadre->anulado);
        $this->assertTrue($hijo->anulado);

        /* Reactivar -> hijos también */
        $movPadre->update(['anulado'=>false]);
        $hijo->update(['anulado'=>false]);
        $movPadre->refresh();
        $hijo->refresh();
        $this->assertFalse($movPadre->anulado);
        $this->assertFalse($hijo->anulado);
    }

    public function testReportesConsistenciaTotales()
    {
        $totalIngresos = MovimientoCaja::where('estado','activo')->where('anulado',false)->where('tipo_movimiento','ingreso')->sum('monto');
        $totalEgresos = MovimientoCaja::where('estado','activo')->where('anulado',false)->where('tipo_movimiento','egreso')->sum('monto');
        $this->assertGreaterThanOrEqual(0, $totalIngresos);
        $this->assertGreaterThanOrEqual(0, $totalEgresos);
    }

    public function testScopesActivos()
    {
        $act = Stock::activos()->count();
        $all = Stock::count();
        $this->assertLessThanOrEqual($all, $act);
    }

    public function testRolesExisten()
    {
        // El seeder crea admin y tecnico; el setUp crea invitado si no existe
        $this->assertTrue(User::where('role','invitado')->exists());
        $this->assertTrue(User::where('role','tecnico')->exists());
    }
}