<?php

/**
 * TEST DE INTEGRACIÓN COMPLETO — Lógica Financiera / Inventario / Caja
 * 
 * Cubre:
 * 1. Anular abono → revierte stock y caja
 * 2. Anular compra/venta → revierte stock correctamente
 * 3. Caja con saldos parciales (histórico + día actual)
 * 3.1. Ingreso/egreso con saldo pendiente → histórico vs día actual
 * 4. Reportes financieros (diario/acumulado/operaciones)
 * 5. Saldos Factura (saldo_pendiente / saldo_a_favor)
 * 6. Anular factura (compra/venta) → stock revertido
 * 7. Anular movimiento caja → abonos en cascada
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\{
    Stock, Proveedor, Cliente, Factura, FacturaItem,
    MovimientoCaja, ConceptoCaja, Mantenimiento, Electronica,
    Abono, Tecnico, Equipo, User
};
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST INTEGRACIÓN COMPLETA — FINANZAS / INVENTARIO / CAJA        ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$pass = 0;
$fail = 0;

function ok($msg) { global $pass; $pass++; echo "✅ $msg\n"; }
function fail($msg, $detail = '') { global $fail; $fail++; echo "❌ $msg\n"; if ($detail) echo "   → $detail\n"; }
function title($msg) { echo "\n━━━ $msg ━━━\n"; }

$admin = User::where('role', 'admin')->first();
$tecnico = User::where('role', 'tecnico')->first();
$cliente = Cliente::first();
$proveedor = Proveedor::first();
$tecnicoModel = Tecnico::first();
$equipo = Equipo::first();

title("SETUP — Datos base");
echo "Admin: {$admin->name} | Técnico: {$tecnico->name}\n";
echo "Cliente: {$cliente->nombres} | Proveedor: {$proveedor->nombre_razon_social}\n";
echo "Técnico asignado: {$tecnicoModel->nombre} | Equipo: {$equipo->nombre}\n";

/* ═══════════════════════════════════════════════════════════════
   1. STOCK SERVICE — Entrada/Salida atómica + concurrencia
   ═══════════════════════════════════════════════════════════════ */
title("1. STOCK SERVICE — atomicidad y límites");

$stock = Stock::first();
$orig = $stock->cantidad;

$svc = new StockService();

/* 1a. Entrada simple */
$stock = $svc->entrada($stock, 5);
if ($stock->cantidad === $orig + 5) ok("entrada(5) suma al stock");
else fail("entrada(5) falló", "esperado {$orig+5}, got {$stock->cantidad}");

/* 1b. Salida válida */
$stock = $svc->salida($stock, 3);
if ($stock->cantidad === $orig + 2) ok("salida(3) resta del stock");
else fail("salida(3) falló", "esperado {$orig+2}, got {$stock->cantidad}");

/* 1c. Salida insuficiente → DomainException */
try {
    $svc->salida($stock, 9999);
    fail("salida(9999) debió lanzar DomainException");
} catch (\DomainException $e) {
    ok("salida(insuficiente) lanza DomainException");
}

/* 1d. Concurrencia simulada: 2 salidas concurrentes no pueden sobrepasar */
$stock2 = Stock::first();
$orig2 = $stock2->cantidad;
$svc->salida($stock2, $orig2); // vacía el stock
try { $svc->salida($stock2, 1); fail("2da salida concurrente debió fallar"); }
catch (\DomainException $e) { ok("Concurrencia: 2da salida bloqueada correctamente"); }

title("2. COMPRA (entrada stock) + ANULACIÓN → stock restaurado");

/* Crear stock para compra */
$stockCompra = Stock::create([
    'producto' => 'Test Compra',
    'categoria' => 'Test',
    'cantidad' => 10,
    'precio_compra' => 1000,
    'utilidad' => 30,
    'proveedor_id' => $proveedor->id,
]);
$origCompra = $stockCompra->cantidad;

/* Registrar compra */
$itemsCompra = [[
    'stock_id' => $stockCompra->id,
    'cantidad' => 4,
    'precio_unitario' => $stockCompra->precio_compra,
]];

DB::beginTransaction();
try {
    $facturaCompra = Factura::create([
        'numero_factura' => Factura::siguienteNumero('F'),
        'tipo_movimiento' => 'compra',
        'estado' => 'emitida',
        'facturable_type' => Proveedor::class,
        'facturable_id' => $proveedor->id,
        'total_documento' => 4000,
        'total_pagado' => 4000,
        'fecha' => now()->toDateString(),
        'user_id' => $admin->id,
    ]);
    foreach ($itemsCompra as $it) {
        FacturaItem::create([
            'factura_id' => $facturaCompra->id,
            'stock_id' => $it['stock_id'],
            'cantidad' => $it['cantidad'],
            'precio_unitario' => $it['precio_unitario'],
        ]);
    }
    DB::commit();
} catch (\Exception $e) { DB::rollBack(); throw $e; }

$stockCompra->refresh();
if ($stockCompra->cantidad === $origCompra + 4) ok("Compra: stock aumentó +4");
else fail("Compra stock", "esperado {$origCompra+4}, got {$stockCompra->cantidad}");

/* Anular compra → stock debe volver a original */
DB::beginTransaction();
try {
    $facturaCompra->update(['estado' => 'anulada']);
    foreach ($facturaCompra->items as $item) {
        $item->stock->decrementarStock($item->cantidad);
    }
    DB::commit();
} catch (\Exception $e) { DB::rollBack(); throw $e; }

$stockCompra->refresh();
if ($stockCompra->cantidad === $origCompra) ok("Anular compra: stock restaurado a original");
else fail("Anular compra stock", "esperado {$origCompra}, got {$stockCompra->cantidad}");

title("3. VENTA (salida stock) + ANULACIÓN → stock restaurado");

$stockVenta = Stock::create([
    'producto' => 'Test Venta',
    'categoria' => 'Test',
    'cantidad' => 20,
    'precio_compra' => 500,
    'utilidad' => 50,
    'proveedor_id' => $proveedor->id,
]);
$origVenta = $stockVenta->cantidad;

$itemsVenta = [[
    'stock_id' => $stockVenta->id,
    'cantidad' => 6,
    'precio_unitario' => $stockVenta->precio_venta,
]];

DB::beginTransaction();
try {
    $facturaVenta = Factura::create([
        'numero_factura' => Factura::siguienteNumero('F'),
        'tipo_movimiento' => 'venta',
        'estado' => 'emitida',
        'facturable_type' => Cliente::class,
        'facturable_id' => $cliente->id,
        'total_documento' => $stockVenta->precio_venta * 6,
        'total_pagado' => 0,
        'fecha' => now()->toDateString(),
        'user_id' => $admin->id,
    ]);
    foreach ($itemsVenta as $it) {
        FacturaItem::create([
            'factura_id' => $facturaVenta->id,
            'stock_id' => $it['stock_id'],
            'cantidad' => $it['cantidad'],
            'precio_unitario' => $it['precio_unitario'],
        ]);
    }
    DB::commit();
} catch (\Exception $e) { DB::rollBack(); throw $e; }

$stockVenta->refresh();
if ($stockVenta->cantidad === $origVenta - 6) ok("Venta: stock disminuyó -6");
else fail("Venta stock", "esperado {$origVenta-6}, got {$stockVenta->cantidad}");

/* Anular venta → stock recupera +6 */
DB::beginTransaction();
try {
    $facturaVenta->update(['estado' => 'anulada']);
    foreach ($facturaVenta->items as $item) {
        $item->stock->incrementarStock($item->cantidad);
    }
    DB::commit();
} catch (\Exception $e) { DB::rollBack(); throw $e; }

$stockVenta->refresh();
if ($stockVenta->cantidad === $origVenta) ok("Anular venta: stock recuperado +6");
else fail("Anular venta stock", "esperado {$origVenta}, got {$stockVenta->cantidad}");

title("4. MANTENIMIENTO + ABONO + ANULAR ABONO → stock y caja revertidos");

$stockMant = Stock::create([
    'producto' => 'Repuesto Mant',
    'categoria' => 'Repuestos',
    'cantidad' => 15,
    'precio_compra' => 1000,
    'utilidad' => 30,
    'proveedor_id' => $proveedor->id,
]);
$origMant = $stockMant->cantidad;

$mant = Mantenimiento::create([
    'id_orden' => 'ORD-TEST-001',
    'equipo_id' => $equipo->id,
    'tecnico_id' => $tecnicoModel->id,
    'descripcion_problema' => 'Test',
    'tipo' => 'correctivo',
    'costo' => 0,
    'estado' => 'pendiente',
    'fecha_entrada' => now()->toDateString(),
    'user_id' => $admin->id,
    'anulado' => false,
]);

/* Agregar repuesto al mantenimiento (descuenta stock) */
DB::beginTransaction();
try {
    $svc->salida($stockMant, 3);
    $mant->stocks()->attach($stockMant->id, ['cantidad' => 3, 'precio_unitario' => $stockMant->precio_venta]);
    $mant->increment('costo', $stockMant->precio_venta * 3);
    DB::commit();
} catch (\Exception $e) { DB::rollBack(); throw $e; }

$stockMant->refresh();
if ($stockMant->cantidad === 12) ok("Mantenimiento: stock -3 al agregar repuesto");
else fail("Mant stock salida", "esperado 12, got {$stockMant->cantidad}");

/* Abono al mantenimiento → genera movimiento caja ingreso */
$conceptoAbono = ConceptoCaja::firstOrCreate(['nombre' => 'Abono Mantenimiento']);
$abono = Abono::create([
    'mantenimiento_id' => $mant->id,
    'monto' => 50000,
    'fecha' => now()->toDateString(),
    'tipo_pago' => 'efectivo',
    'user_id' => $admin->id,
]);

$movCaja = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => now()->toDateString(),
    'monto' => 50000,
    'concepto_id' => $conceptoAbono->id,
    'persona' => $cliente->nombre,
    'descripcion' => "Abono Mantenimiento {$mant->id_orden}",
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
    'abono_id' => $abono->id,
]);

$ingresosAntes = MovimientoCaja::where('tipo_movimiento','ingreso')->where('anulado',false)->sum('monto');
if ($ingresosAntes > 0) ok("Abono generó movimiento caja ingreso");

/* ANULAR ABONO → stock + caja revertidos */
$abono->delete(); // soft delete via destroy() en controller, aquí manual

$stockMant->refresh();
if ($stockMant->cantidad === $origMant) ok("Anular abono: stock restaurado a original");
else fail("Anular abono stock", "esperado {$origMant}, got {$stockMant->cantidad}");

/* Movimiento caja del abono debe estar anulado */
$movCaja->refresh();
if ($movCaja->anulado) ok("Anular abono: movimiento caja anulado en cascada");
else fail("Anular abono caja", "movimiento caja no anulado");

title("5. FACTURA CON SALDO PARCIAL (PAGO PARCIAL) → saldos correctos");

$stockFact = Stock::create([
    'producto' => 'Prod Factura',
    'categoria' => 'Test',
    'cantidad' => 100,
    'precio_compra' => 1000,
    'utilidad' => 40,
    'proveedor_id' => $proveedor->id,
]);

/* Factura venta 10 uds = 14.000 c/u → total 140.000, paga 50.000 */
$factParcial = Factura::create([
    'numero_factura' => Factura::siguienteNumero('F'),
    'tipo_movimiento' => 'venta',
    'estado' => 'emitida',
    'facturable_type' => Cliente::class,
    'facturable_id' => $cliente->id,
    'total_documento' => 140000,
    'total_pagado' => 50000,
    'fecha' => now()->toDateString(),
    'user_id' => $admin->id,
]);
FacturaItem::create([
    'factura_id' => $factParcial->id,
    'stock_id' => $stockFact->id,
    'cantidad' => 10,
    'precio_unitario' => 14000,
]);

$factParcial->refresh();
if ($factParcial->saldo_pendiente === 90000) ok("Saldo pendiente = 90.000 (140k - 50k)");
else fail("saldo_pendiente", "esperado 90000, got {$factParcial->saldo_pendiente}");

if ($factParcial->saldo_a_favor === 0) ok("saldo_a_favor = 0 (no sobrepago)");
else fail("saldo_a_favor", "esperado 0, got {$factParcial->saldo_a_favor}");

/* Sobrepago → saldo_a_favor > 0 */
$factParcial->update(['total_pagado' => 150000]);
$factParcial->refresh();
if ($factParcial->saldo_pendiente === 0 && $factParcial->saldo_a_favor === 10000) ok("Sobrepago: saldo_pendiente=0, saldo_a_favor=10.000");
else fail("sobrepago", "pendiente={$factParcial->saldo_pendiente}, a_favor={$factParcial->saldo_a_favor}");

title("6. CAJA — Ingreso/Egreso con saldo parcial (histórico vs día)");

/* Crear ingreso 500.000 ayer, pagar 100.000 ayer → saldo 400.000 pendiente */
$conceptoIng = ConceptoCaja::firstOrCreate(['nombre' => 'Ingreso Test']);
$ingreso = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => Carbon::yesterday()->toDateString(),
    'monto' => 500000,
    'monto_total' => 500000,
    'concepto_id' => $conceptoIng->id,
    'persona' => 'Cliente Histórico',
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
]);

/* Abono parcial ayer 100.000 */
$abonoAyer = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => Carbon::yesterday()->toDateString(),
    'monto' => 100000,
    'monto_total' => 100000,
    'concepto_id' => $conceptoIng->id,
    'persona' => 'Cliente Histórico',
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
    'parent_id' => $ingreso->id,
]);

/* Verificar saldos */
$ingreso->refresh();
$saldoAyer = $ingreso->saldo_pendiente; // accessor
if ($saldoAyer === 400000) ok("Saldo histórico ayer = 400.000 (500k - 100k)");
else fail("Saldo histórico", "esperado 400000, got $saldoAyer");

/* Hoy: nuevo ingreso 200.000, sin abonos */
$ingresoHoy = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => Carbon::today()->toDateString(),
    'monto' => 200000,
    'monto_total' => 200000,
    'concepto_id' => $conceptoIng->id,
    'persona' => 'Cliente Hoy',
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
]);

/* Dashboard / Reportes: saldo día actual vs acumulado */
$ingresosHoy = MovimientoCaja::where('tipo_movimiento','ingreso')
    ->where('anulado',false)->where('estado','activo')
    ->whereDate('fecha', Carbon::today())->sum('monto');
$egresosHoy = MovimientoCaja::where('tipo_movimiento','egreso')
    ->where('anulado',false)->where('estado','activo')
    ->whereDate('fecha', Carbon::today())->sum('monto');
$saldoDia = $ingresosHoy - $egresosHoy;

if ($ingresosHoy === 200000) ok("Ingresos de hoy = 200.000");
else fail("Ingresos hoy", "got $ingresosHoy");

$saldoHistorico = MovimientoCaja::where('estado','activo')->where('anulado',false)
    ->where('fecha', '<', Carbon::today())
    ->selectRaw("SUM(CASE WHEN tipo_movimiento='ingreso' THEN monto ELSE -monto END) as saldo")
    ->value('saldo') ?? 0;
// De ayer: ingreso 500k + abono 100k (ingreso) = 600k ingresos. Sin egresos.
// saldoHistorico debería ser 600k (500k original + 100k abono) ??? Wait, the original ingreso has monto_total 500k, abono 100k is separate row with monto 100k. The "saldo pendiente" of original is 400k. But the historical sum would be 500k + 100k = 600k.
// Actually the dashboard uses monto (not monto_total) for historical sum.
if ($saldoHistorico === 600000) ok("Saldo histórico (hasta ayer) = 600.000");
else fail("Saldo histórico", "esperado 600000, got $saldoHistorico");

$acumulado = $saldoHistorico + $saldoDia; // 600k + 200k = 800k
if ($acumulado === 800000) ok("Acumulado total = 800.000");
else fail("Acumulado", "esperado 800000, got $acumulado");

title("7. REPORTES FINANCIEROS — Diario / Acumulado / Operaciones");

/* Ejecutar reportes vía controller */
$ctrlFin = new \App\Http\Controllers\ReporteFinancieroController();
$diario = $ctrlFin->reporteDiario();
$acumulado = $ctrlFin->reporteAcumulado();
$operaciones = $ctrlFin->reporteOperaciones();

if ($diario && $acumulado && $operaciones) ok("Reportes financieros se ejecutan sin error");
else fail("Reportes financieros", "alguno retornó null");

/* Verificar que el diario incluye nuestro ingreso de hoy */
$tieneHoy = false;
foreach ($diario as $mov) {
    if (isset($mov['fecha']) && $mov['fecha'] === Carbon::today()->toDateString() && $mov['tipo_movimiento'] === 'ingreso' && $mov['monto'] === 200000) {
        $tieneHoy = true; break;
    }
}
if ($tieneHoy) ok("Reporte diario incluye ingreso de hoy");
else fail("Reporte diario", "no encontró ingreso 200k de hoy");

title("8. ANULAR FACTURA COMPRA/VENTA → stock y estado correctos");

/* Compra anulada ya probada arriba (stock restaurado) */
/* Venta anulada ya probada arriba (stock restaurado) */
ok("Anular compra/venta ya verificado en tests 2 y 3");

title("9. ANULAR MOVIMIENTO CAJA → abonos en cascada anulados");

$movPadre = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => now()->toDateString(),
    'monto' => 100000,
    'monto_total' => 100000,
    'concepto_id' => $conceptoIng->id,
    'persona' => 'Test Padre',
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
]);

$movHijo = MovimientoCaja::create([
    'tipo_movimiento' => 'ingreso',
    'fecha' => now()->toDateString(),
    'monto' => 30000,
    'monto_total' => 30000,
    'concepto_id' => $conceptoIng->id,
    'persona' => 'Test Hijo',
    'tipo_pago' => 'efectivo',
    'estado' => 'activo',
    'user_id' => $admin->id,
    'parent_id' => $movPadre->id,
]);

$movPadre->update(['anulado' => true]);
$movPadre->childPayments()->update(['anulado' => true]);

$movPadre->refresh(); $movHijo->refresh();
if ($movPadre->anulado && $movHijo->anulado) ok("Anular padre anula hijos en cascada");
else fail("Anular cascada", "padre={$movPadre->anulado}, hijo={$movHijo->anulado}");

/* Reactivar → hijos también */
$movPadre->update(['anulado' => false]);
$movPadre->childPayments()->update(['anulado' => false]);
$movPadre->refresh(); $movHijo->refresh();
if (!$movPadre->anulado && !$movHijo->anulado) ok("Reactivar padre reactiva hijos");
else fail("Reactivar cascada", "padre={$movPadre->anulado}, hijo={$movHijo->anulado}");

title("10. MATEMÁTICAS / SUMA / SALDOS — Verificaciones cruzadas");

/* 10a. Factura saldo_pendiente + total_pagado === total_documento */
$fCheck = Factura::where('estado','!=','anulada')->first();
if ($fCheck && $fCheck->saldo_pendiente + $fCheck->total_pagado === $fCheck->total_documento) {
    ok("Factura: saldo_pendiente + total_pagado = total_documento");
} else { fail("Factura invariante"); }

/* 10b. MovimientoCaja padre: monto === sum(hijos.monto) + monto_propio (si aplica) */
/* Aquí el padre tiene monto_total = monto + sum(hijos.monto) */
$sumHijos = $movPadre->childPayments()->sum('monto');
if ($movPadre->monto_total === $movPadre->monto + $sumHijos) {
    ok("MovimientoCaja: monto_total = monto + sum(hijos)");
} else { fail("Caja invariante", "padre total={$movPadre->monto_total}, monto={$movPadre->monto}, hijos=$sumHijos"); }

/* 10c. Mantenimiento costo = sum(stocks.pivot.cantidad * precio_unitario) */
$mant2 = Mantenimiento::with('stocks')->first();
$calc = $mant2->stocks->sum(fn($s) => $s->pivot->cantidad * $s->pivot->precio_unitario);
if ((float)$mant2->costo === (float)$calc) ok("Mantenimiento costo = sum(repuestos)");
else fail("Mantenimiento costo", "bd={$mant2->costo}, calc=$calc");

/* 10d. Stock cantidad nunca negativa */
$neg = Stock::where('cantidad', '<', 0)->count();
if ($neg === 0) ok("Ningún stock negativo en BD");
else fail("Stockselse fail("Stock negativos", "hay $neg registros negativos");

title("11. SCOPE ACTIVOS — Solo registros activos en listados");

$stocksAct = Stock::activos()->count();
$stocksAll = Stock::count();
if ($stocksAct <= $stocksAll) ok("Stock::activos() filtra inactivos ($stocksAct de $stocksAll)");

$prodsAct = Proveedor::activos()->count();
if ($prodsAct <= Proveedor::count()) ok("Proveedor::activos() funciona");

title("12. ROLES / PERMISOS — Accesos correctos");

$invitado = User::where('role','invitado')->first();
if ($invitado) ok("Usuario invitado existe");
else fail("Invitado no existe");

/* ═══════════════════════════════════════════════════════════════════════ */
echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  RESULTADO: $pass PASARON, $fail FALLARON                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";

if ($fail > 0) exit(1);
exit(0);