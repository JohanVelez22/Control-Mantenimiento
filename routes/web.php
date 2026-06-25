<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Rutas WEB
|--------------------------------------------------------------------------
*/

// Redirigir la raíz al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de invitados (no autenticados)
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro',[AuthController::class, 'register']);
});

// Rutas protegidas (autenticados)
Route::middleware(['auth', \App\Http\Middleware\PreventBackHistory::class])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Descartar alerta de electrónica (llamado por JS)
    Route::post('/electronicas/dismiss-alert', function () {
        session()->forget('alertas_electronica');
        return response()->noContent();
    })->name('electronicas.dismiss-alert');

    // ─── Reportes de módulos ───────────────────────────────────────────
    Route::get('/mantenimientos-reportes', [MantenimientoController::class, 'reportes'])->name('mantenimientos.reportes');
    Route::get('/electronicas-reportes',   [App\Http\Controllers\ElectronicaController::class, 'reportes'])->name('electronicas.reportes');
    Route::get('/reportes',                [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');

    // ─── Reportes Financieros (Diario, Acumulado, Operaciones) ────────
    Route::prefix('reportes-financieros')->name('reportes.financiero.')->group(function () {
        Route::get('/diario',      [App\Http\Controllers\ReporteFinancieroController::class, 'reporteDiario'])->name('diario');
        Route::get('/acumulado',   [App\Http\Controllers\ReporteFinancieroController::class, 'reporteAcumulado'])->name('acumulado');
        Route::get('/operaciones', [App\Http\Controllers\ReporteFinancieroController::class, 'reporteOperaciones'])->name('operaciones');
    });

    Route::get('/mantenimientos/{mantenimiento}/factura', [MantenimientoController::class, 'factura'])->name('mantenimientos.factura');

    // ─── Módulos generales ────────────────────────────────────────────
    Route::resource('clientes', ClienteController::class)->except(['destroy']);
    Route::resource('equipos', EquipoController::class)->except(['destroy']);
    Route::resource('tecnicos', TecnicoController::class)->except(['destroy']);
    Route::resource('stocks', App\Http\Controllers\StockController::class)->except(['destroy']);
    Route::resource('electronicas',App\Http\Controllers\ElectronicaController::class)->except(['destroy']);
    Route::get('electronicas/{electronica}/factura', [App\Http\Controllers\ElectronicaController::class, 'factura'])->name('electronicas.factura');
    Route::post('electronicas/{electronica}/anular', [App\Http\Controllers\ElectronicaController::class, 'anular'])->name('electronicas.anular');
    Route::resource('mantenimientos', MantenimientoController::class)->except(['destroy']);

    // ─── Proveedores ──────────────────────────────────────────────────
    Route::resource('proveedores', App\Http\Controllers\ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->except(['destroy']);

    // ─── Inventario: Compras y Ventas ─────────────────────────────────
    Route::prefix('inventario')->name('inventario.')->group(function () {
        // Compra de stock
        Route::get( '/compra/nueva',  [App\Http\Controllers\MovimientoInventarioController::class, 'createCompra'])->name('compra.create');
        Route::post('/compra',        [App\Http\Controllers\MovimientoInventarioController::class, 'registrarCompra'])->name('compra.store');

        // Venta de stock
        Route::get( '/venta/nueva',   [App\Http\Controllers\MovimientoInventarioController::class, 'createVenta'])->name('venta.create');
        Route::post('/venta',         [App\Http\Controllers\MovimientoInventarioController::class, 'registrarVenta'])->name('venta.store');

        // Listado e impresión de facturas
        Route::get('/facturas',                     [App\Http\Controllers\MovimientoInventarioController::class, 'facturas'])->name('facturas');
        Route::get('/facturas/{factura}',            [App\Http\Controllers\MovimientoInventarioController::class, 'showFactura'])->name('facturas.show');
        Route::get('/facturas/{factura}/edit',       [App\Http\Controllers\MovimientoInventarioController::class, 'editFactura'])->name('facturas.edit');
        Route::put('/facturas/{factura}',            [App\Http\Controllers\MovimientoInventarioController::class, 'updateFactura'])->name('facturas.update');
        Route::get('/facturas/{factura}/imprimir',   [App\Http\Controllers\MovimientoInventarioController::class, 'printFactura'])->name('facturas.print');
        Route::post('/facturas/{factura}/anular',    [App\Http\Controllers\MovimientoInventarioController::class, 'anularFactura'])->name('facturas.anular');
    });

    // ─── Caja ─────────────────────────────────────────────────────────
    Route::resource('caja', App\Http\Controllers\MovimientoCajaController::class)->except(['show', 'destroy'])->parameters([
        'caja' => 'movimiento'
    ]);
    Route::get( 'caja/{movimiento}/print',     [App\Http\Controllers\MovimientoCajaController::class, 'print'])->name('caja.print');
    Route::post('caja/{movimiento}/duplicate', [App\Http\Controllers\MovimientoCajaController::class, 'duplicate'])->name('caja.duplicate');
    Route::post('caja/{movimiento}/anular',    [App\Http\Controllers\MovimientoCajaController::class, 'anular'])->name('caja.anular');
    Route::post('caja/concepto',               [App\Http\Controllers\MovimientoCajaController::class, 'storeConcepto'])->name('caja.concepto.store');

    Route::resource('conceptos', App\Http\Controllers\ConceptoCajaController::class)->only(['index', 'store', 'update', 'destroy']);

    // ─── Mantenimiento: acciones extra ────────────────────────────────
    Route::post('mantenimientos/{mantenimiento}/duplicate', [MantenimientoController::class, 'duplicate'])->name('mantenimientos.duplicate');
    Route::post('mantenimientos/{mantenimiento}/anular',    [MantenimientoController::class, 'anular'])->name('mantenimientos.anular');

    // ─── Abonos (anidados bajo mantenimiento y electronica) ─────────────────────────
    Route::post('mantenimientos/{mantenimiento}/abonos', [App\Http\Controllers\AbonoController::class, 'store'])->name('abonos.store');
    Route::delete('abonos/{abono}',                      [App\Http\Controllers\AbonoController::class, 'destroy'])->name('abonos.destroy');
    Route::post('electronicas/{electronica}/abonos',     [App\Http\Controllers\ElectronicaAbonoController::class, 'store'])->name('electronicas.abonos.store');
    Route::delete('electronicas/abonos/{abono}',         [App\Http\Controllers\ElectronicaAbonoController::class, 'destroy'])->name('electronicas.abonos.destroy');

    // ─── Repuestos de stock (anidados bajo mantenimiento y electronica) ─────────────
    Route::post('mantenimientos/{mantenimiento}/stocks', [App\Http\Controllers\MantenimientoStockController::class, 'store'])->name('mantenimientos.stocks.store');
    Route::delete('mantenimientos/{mantenimiento}/stocks/{stock_id}', [App\Http\Controllers\MantenimientoStockController::class, 'destroy'])->name('mantenimientos.stocks.destroy');
    Route::post('electronicas/{electronica}/stocks', [App\Http\Controllers\ElectronicaStockController::class, 'store'])->name('electronicas.stocks.store');
    Route::delete('electronicas/{electronica}/stocks/{stock_id}', [App\Http\Controllers\ElectronicaStockController::class, 'destroy'])->name('electronicas.stocks.destroy');

    // ─── Cierre de Caja ───────────────────────────────────────────────
    Route::get(   'cierre',            [App\Http\Controllers\CierreCajaController::class, 'index'])->name('cierre.index');
    Route::post(  'cierre',            [App\Http\Controllers\CierreCajaController::class, 'store'])->name('cierre.store');
    Route::delete('cierre/{cierre}',   [App\Http\Controllers\CierreCajaController::class, 'destroy'])->name('cierre.destroy');

    // ─── Módulo de Usuarios (ADMIN y TÉCNICO) ─────────────────────────
    Route::middleware(['role:admin,tecnico'])->group(function () {
        Route::resource('usuarios', UserController::class)->except(['destroy']);
        Route::post('usuarios/{usuario}/change-password', [UserController::class, 'changePassword'])->name('usuarios.change-password');
    });

    // ─── Configuración de Empresa ──────────────────────────────────────
    Route::get('/configuracion',  [App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion', [App\Http\Controllers\ConfiguracionController::class, 'update'])->name('configuracion.update');

    // ─── Eventos / Auditoría ──────────────────────────────────────────
    Route::get('/eventos', [App\Http\Controllers\EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/{evento}', [App\Http\Controllers\EventoController::class, 'show'])->name('eventos.show');
});
