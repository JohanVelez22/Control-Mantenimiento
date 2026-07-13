<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventBackHistory;

/*
|--------------------------------------------------------------------------
| Rutas WEB — Control de Acceso por Rol
|--------------------------------------------------------------------------
| admin  : acceso total.
| tecnico : crear y visualizar; NO anular; editar requiere contraseña de admin.
| invitado: SOLO consultar mantenimientos/electrónicas (ver y factura PDF).
|
| La lógica de roles vive en el middleware `role` (CheckRole) y en los
| Gates/policies de AppServiceProvider. Aquí solo se declaran las rutas.
*/

// Redirigir la raíz al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de invitados (no autenticados)
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
});

// Rutas protegidas (autenticados)
Route::middleware(['auth', PreventBackHistory::class])->group(function () {

    // Logout (cualquier usuario autenticado)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Descartar alerta de electrónica (JS)
    Route::post('/electronicas/dismiss-alert', function () {
        session()->forget('alertas_electronica');
        return response()->noContent();
    })->name('electronicas.dismiss-alert');

    // Dashboard: todos los autenticados (invitado ve resumen de solo lectura)
    Route::middleware('role:admin,tecnico,invitado')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // ─── Solo ADMIN y TÉCNICO ─────────────────────────────────────────
    Route::middleware('role:admin,tecnico')->group(function () {

        // Reportes de módulos
        Route::get('/mantenimientos-reportes', [MantenimientoController::class, 'reportes'])->name('mantenimientos.reportes');
        Route::get('/electronicas-reportes',   [App\Http\Controllers\ElectronicaController::class, 'reportes'])->name('electronicas.reportes');
        Route::get('/stocks-reportes',         [App\Http\Controllers\StockController::class, 'reportes'])->name('stocks.reportes');
        Route::get('/reportes',                [App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');

        // Reportes Financieros
        Route::prefix('reportes-financieros')->name('reportes.financiero.')->group(function () {
            Route::get('/diario',      [App\Http\Controllers\ReporteFinancieroController::class, 'reporteDiario'])->name('diario');
            Route::get('/acumulado',   [App\Http\Controllers\ReporteFinancieroController::class, 'reporteAcumulado'])->name('acumulado');
            Route::get('/operaciones', [App\Http\Controllers\ReporteFinancieroController::class, 'reporteOperaciones'])->name('operaciones');
        });

        // Módulos generales
        Route::get('api/municipios', [ClienteController::class, 'municipios'])->name('api.municipios');
        Route::resource('clientes', ClienteController::class)->except(['destroy']);
        Route::resource('equipos', EquipoController::class)->except(['destroy']);
        Route::resource('tecnicos', TecnicoController::class)->except(['destroy']);
        Route::resource('stocks/categorias', App\Http\Controllers\CategoriaStockController::class)->names('stocks.categorias')->except(['create', 'show', 'edit']);
        Route::resource('stocks', App\Http\Controllers\StockController::class)->except(['destroy']);
        Route::get('stocks/{stock}/print', [App\Http\Controllers\StockController::class, 'print'])->name('stocks.print');

        // Mantenimientos: mutaciones (lectura la gestiona el grupo de invitado)
        Route::resource('mantenimientos', MantenimientoController::class)->except(['destroy', 'index', 'show']);
        Route::post('mantenimientos/{mantenimiento}/duplicate', [MantenimientoController::class, 'duplicate'])->name('mantenimientos.duplicate');

        // Electrónicas: mutaciones
        Route::resource('electronicas', App\Http\Controllers\ElectronicaController::class)->except(['destroy', 'index', 'show']);

        // Proveedores
        Route::resource('proveedores', App\Http\Controllers\ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->except(['destroy']);

        // Inventario: Compras y Ventas
        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get( '/compra/nueva',  [App\Http\Controllers\MovimientoInventarioController::class, 'createCompra'])->name('compra.create');
            Route::post('/compra',        [App\Http\Controllers\MovimientoInventarioController::class, 'registrarCompra'])->name('compra.store');
            Route::get( '/venta/nueva',   [App\Http\Controllers\MovimientoInventarioController::class, 'createVenta'])->name('venta.create');
            Route::post('/venta',         [App\Http\Controllers\MovimientoInventarioController::class, 'registrarVenta'])->name('venta.store');
            Route::get('/facturas',                     [App\Http\Controllers\MovimientoInventarioController::class, 'facturas'])->name('facturas');
            Route::get('/facturas/{factura}',            [App\Http\Controllers\MovimientoInventarioController::class, 'showFactura'])->name('facturas.show');
            Route::get('/facturas/{factura}/edit',       [App\Http\Controllers\MovimientoInventarioController::class, 'editFactura'])->name('facturas.edit');
            Route::put('/facturas/{factura}',            [App\Http\Controllers\MovimientoInventarioController::class, 'updateFactura'])->name('facturas.update');
            Route::get('/facturas/{factura}/imprimir',   [App\Http\Controllers\MovimientoInventarioController::class, 'printFactura'])->name('facturas.print');
        });

        // Caja
        Route::resource('caja', App\Http\Controllers\MovimientoCajaController::class)->except(['show', 'destroy'])->parameters(['caja' => 'movimiento']);
        Route::get( 'caja/{movimiento}/print',     [App\Http\Controllers\MovimientoCajaController::class, 'print'])->name('caja.print');
        Route::post('caja/{movimiento}/duplicate', [App\Http\Controllers\MovimientoCajaController::class, 'duplicate'])->name('caja.duplicate');
        Route::post('caja/{movimiento}/abonos',    [App\Http\Controllers\MovimientoCajaController::class, 'storeAbono'])->name('caja.abonos.store');
        Route::post('caja/concepto',               [App\Http\Controllers\MovimientoCajaController::class, 'storeConcepto'])->name('caja.concepto.store');
        Route::resource('conceptos', App\Http\Controllers\ConceptoCajaController::class)->except(['create', 'show']);

        // Abonos (anidados)
        Route::post('mantenimientos/{mantenimiento}/abonos', [App\Http\Controllers\AbonoController::class, 'store'])->name('abonos.store');
        Route::delete('abonos/{abono}',                      [App\Http\Controllers\AbonoController::class, 'destroy'])->name('abonos.destroy');
        Route::post('electronicas/{electronica}/abonos',     [App\Http\Controllers\ElectronicaAbonoController::class, 'store'])->name('electronicas.abonos.store');
        Route::delete('electronicas/abonos/{abono}',         [App\Http\Controllers\ElectronicaAbonoController::class, 'destroy'])->name('electronicas.abonos.destroy');

        // Repuestos de stock (anidados)
        Route::post('mantenimientos/{mantenimiento}/stocks', [App\Http\Controllers\MantenimientoStockController::class, 'store'])->name('mantenimientos.stocks.store');
        Route::delete('mantenimientos/{mantenimiento}/stocks/{stock_id}', [App\Http\Controllers\MantenimientoStockController::class, 'destroy'])->name('mantenimientos.stocks.destroy');
        Route::post('electronicas/{electronica}/stocks', [App\Http\Controllers\ElectronicaStockController::class, 'store'])->name('electronicas.stocks.store');
        Route::delete('electronicas/{electronica}/stocks/{stock_id}', [App\Http\Controllers\ElectronicaStockController::class, 'destroy'])->name('electronicas.stocks.destroy');

        // Cierre de Caja
        Route::get(   'cierre',            [App\Http\Controllers\CierreCajaController::class, 'index'])->name('cierre.index');
        Route::post(  'cierre',            [App\Http\Controllers\CierreCajaController::class, 'store'])->name('cierre.store');
        Route::delete('cierre/{cierre}',   [App\Http\Controllers\CierreCajaController::class, 'destroy'])->name('cierre.destroy');

        // Configuración de Empresa
        Route::get('/configuracion',  [App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion', [App\Http\Controllers\ConfiguracionController::class, 'update'])->name('configuracion.update');

        // Eventos / Auditoría
        Route::get('/eventos', [App\Http\Controllers\EventoController::class, 'index'])->name('eventos.index');
        Route::get('/eventos/{evento}', [App\Http\Controllers\EventoController::class, 'show'])->name('eventos.show');

        // Usuarios (admin y técnico pueden gestionar; anular es solo admin)
        Route::resource('usuarios', UserController::class)->except(['destroy']);
        Route::post('usuarios/{usuario}/change-password', [UserController::class, 'changePassword'])->name('usuarios.change-password');
    });

    // ─── INVITADO: solo consultar mantenimientos y electrónicas ──────
    Route::middleware('role:admin,tecnico,invitado')->group(function () {
        Route::get('mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
        Route::get('mantenimientos/{mantenimiento}', [MantenimientoController::class, 'show'])->name('mantenimientos.show');
        Route::get('mantenimientos/{mantenimiento}/factura', [MantenimientoController::class, 'factura'])->name('mantenimientos.factura');

        Route::get('electronicas', [App\Http\Controllers\ElectronicaController::class, 'index'])->name('electronicas.index');
        Route::get('electronicas/{electronica}', [App\Http\Controllers\ElectronicaController::class, 'show'])->name('electronicas.show');
        Route::get('electronicas/{electronica}/factura', [App\Http\Controllers\ElectronicaController::class, 'factura'])->name('electronicas.factura');
    });

    // ─── Admin y Técnico: anular (técnico requiere contraseña de admin) ─────
    Route::middleware('role:admin,tecnico')->group(function () {
        Route::post('clientes/{cliente}/anular', [ClienteController::class, 'anular'])->name('clientes.anular');
        Route::post('equipos/{equipo}/anular', [EquipoController::class, 'anular'])->name('equipos.anular');
        Route::post('tecnicos/{tecnico}/anular', [TecnicoController::class, 'anular'])->name('tecnicos.anular');
        Route::post('stocks/{stock}/anular', [App\Http\Controllers\StockController::class, 'anular'])->name('stocks.anular');
        Route::post('electronicas/{electronica}/anular', [App\Http\Controllers\ElectronicaController::class, 'anular'])->name('electronicas.anular');
        Route::post('mantenimientos/{mantenimiento}/anular', [MantenimientoController::class, 'anular'])->name('mantenimientos.anular');
        Route::post('proveedores/{proveedor}/anular', [App\Http\Controllers\ProveedorController::class, 'anular'])->name('proveedores.anular');
        Route::post('caja/{movimiento}/anular', [App\Http\Controllers\MovimientoCajaController::class, 'anular'])->name('caja.anular');
        Route::post('inventario/facturas/{factura}/anular', [App\Http\Controllers\MovimientoInventarioController::class, 'anularFactura'])->name('inventario.facturas.anular');
        Route::post('usuarios/{usuario}/anular', [UserController::class, 'anular'])->name('usuarios.anular');
    });
});
