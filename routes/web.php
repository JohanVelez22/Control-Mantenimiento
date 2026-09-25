<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaStockController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConceptoCajaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElectronicaAbonoController;
use App\Http\Controllers\ElectronicaController;
use App\Http\Controllers\ElectronicaStockController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\MantenimientoStockController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteFinancieroController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas (autenticados)
Route::middleware(['auth', 'prevent-back-history'])->group(function () {

    // Logout (cualquier usuario autenticado)
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:10,1')->name('logout');

    // Descartar alerta de electrónica (JS)
    Route::post('/electronicas/dismiss-alert', function () {
        session()->forget('alertas_electronica');

        return response()->noContent();
    })->name('electronicas.dismiss-alert');

    // Dashboard: todos los autenticados (invitado ve resumen de solo lectura)
    Route::middleware('role:admin,tecnico,invitado')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // ─── INVITADO: Panel dedicado y consultas (Protegidas con Throttling) ─────────────────
    Route::middleware(['role:invitado', 'throttle:30,1'])->group(function () {
        Route::get('/guest/dashboard', [GuestController::class, 'dashboard'])->name('guest.dashboard');
        Route::get('/guest/search', [GuestController::class, 'search'])->name('guest.search');
        Route::get('/consulta/mantenimientos', [MantenimientoController::class, 'consulta'])->name('consulta.mantenimientos');
        Route::get('/consulta/electronicas', [ElectronicaController::class, 'consulta'])->name('consulta.electronicas');
    });

    // ─── Solo ADMIN y TÉCNICO ─────────────────────────────────────────
    Route::middleware('role:admin,tecnico')->group(function () {

        // Reportes de módulos
        Route::get('/mantenimientos-reportes', [MantenimientoController::class, 'reportes'])->name('mantenimientos.reportes');
        Route::get('/electronicas-reportes', [ElectronicaController::class, 'reportes'])->name('electronicas.reportes');
        Route::get('/stocks-reportes', [StockController::class, 'reportes'])->name('stocks.reportes');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

        // Reportes Financieros
        Route::prefix('reportes-financieros')->name('reportes.financiero.')->group(function () {
            Route::get('/diario', [ReporteFinancieroController::class, 'reporteDiario'])->name('diario');
            Route::get('/acumulado', [ReporteFinancieroController::class, 'reporteAcumulado'])->name('acumulado');
            Route::get('/operaciones', [ReporteFinancieroController::class, 'reporteOperaciones'])->name('operaciones');
        });

        // Módulos generales
        Route::get('api/municipios', [ClienteController::class, 'municipios'])->name('api.municipios');
        Route::resource('clientes', ClienteController::class)->except(['destroy']);
        Route::resource('equipos', EquipoController::class)->except(['destroy']);
        Route::resource('tecnicos', TecnicoController::class)->except(['destroy']);
        Route::resource('stocks/categorias', CategoriaStockController::class)->names('stocks.categorias')->except(['create', 'show', 'edit']);
        Route::resource('stocks', StockController::class)->except(['destroy']);
        Route::get('stocks/{stock}/print', [StockController::class, 'print'])->name('stocks.print');

        // Cotizaciones
        Route::resource('cotizaciones', CotizacionController::class)->parameters(['cotizaciones' => 'cotizacion'])->except(['destroy']);
        Route::post('cotizaciones/{cotizacion}/convertir', [CotizacionController::class, 'convertir'])->name('cotizaciones.convertir');
        Route::post('cotizaciones/{cotizacion}/anular', [CotizacionController::class, 'anular'])->middleware('throttle:10,1')->name('cotizaciones.anular');
        Route::post('cotizaciones/{cotizacion}/rechazar', [CotizacionController::class, 'rechazar'])->middleware('throttle:10,1')->name('cotizaciones.rechazar');
        Route::post('cotizaciones/{cotizacion}/reactivar', [CotizacionController::class, 'reactivar'])->middleware('throttle:10,1')->name('cotizaciones.reactivar');
        Route::get('cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');

        // Mantenimientos: mutaciones (lectura la gestiona el grupo de invitado)
        Route::resource('mantenimientos', MantenimientoController::class)->except(['destroy', 'index', 'show']);
        Route::post('mantenimientos/{mantenimiento}/duplicate', [MantenimientoController::class, 'duplicate'])->name('mantenimientos.duplicate');

        // Electrónicas: mutaciones
        Route::resource('electronicas', ElectronicaController::class)->except(['destroy', 'index', 'show']);

        // Proveedores
        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->except(['destroy']);

        // Inventario: Compras y Ventas
        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('/compra/nueva', [MovimientoInventarioController::class, 'createCompra'])->name('compra.create');
            Route::post('/compra', [MovimientoInventarioController::class, 'registrarCompra'])->name('compra.store');
            Route::get('/venta/nueva', [MovimientoInventarioController::class, 'createVenta'])->name('venta.create');
            Route::post('/venta', [MovimientoInventarioController::class, 'registrarVenta'])->name('venta.store');
            Route::get('/facturas', [MovimientoInventarioController::class, 'facturas'])->name('facturas');
            Route::get('/facturas/{factura}', [MovimientoInventarioController::class, 'showFactura'])->name('facturas.show');
            Route::get('/facturas/{factura}/edit', [MovimientoInventarioController::class, 'editFactura'])->name('facturas.edit');
            Route::put('/facturas/{factura}', [MovimientoInventarioController::class, 'updateFactura'])->name('facturas.update');
            Route::get('/facturas/{factura}/imprimir', [MovimientoInventarioController::class, 'printFactura'])->name('facturas.print');
        });

        // Caja
        Route::resource('caja', MovimientoCajaController::class)->except(['destroy'])->parameters(['caja' => 'movimiento']);
        Route::get('caja/{movimiento}/print', [MovimientoCajaController::class, 'print'])->name('caja.print');
        Route::post('caja/{movimiento}/duplicate', [MovimientoCajaController::class, 'duplicate'])->name('caja.duplicate');
        Route::post('caja/{movimiento}/abonos', [MovimientoCajaController::class, 'storeAbono'])->name('caja.abonos.store');
        Route::post('caja/concepto', [MovimientoCajaController::class, 'storeConcepto'])->name('caja.concepto.store');
        Route::resource('conceptos', ConceptoCajaController::class)->except(['create', 'show']);

        // Abonos (anidados)
        Route::post('mantenimientos/{mantenimiento}/abonos', [AbonoController::class, 'store'])->name('abonos.store');
        Route::delete('abonos/{abono}', [AbonoController::class, 'destroy'])->name('abonos.destroy');
        Route::post('electronicas/{electronica}/abonos', [ElectronicaAbonoController::class, 'store'])->name('electronicas.abonos.store');
        Route::delete('electronicas/abonos/{abono}', [ElectronicaAbonoController::class, 'destroy'])->name('electronicas.abonos.destroy');

        // Repuestos de stock (anidados)
        Route::post('mantenimientos/{mantenimiento}/stocks', [MantenimientoStockController::class, 'store'])->name('mantenimientos.stocks.store');
        Route::delete('mantenimientos/{mantenimiento}/stocks/{stock_id}', [MantenimientoStockController::class, 'destroy'])->name('mantenimientos.stocks.destroy');
        Route::post('electronicas/{electronica}/stocks', [ElectronicaStockController::class, 'store'])->name('electronicas.stocks.store');
        Route::delete('electronicas/{electronica}/stocks/{stock_id}', [ElectronicaStockController::class, 'destroy'])->name('electronicas.stocks.destroy');

        // Cierre de Caja
        Route::get('cierre', [CierreCajaController::class, 'index'])->name('cierre.index');
        Route::post('cierre', [CierreCajaController::class, 'store'])->name('cierre.store');
        Route::get('cierre/{cierre}', [CierreCajaController::class, 'show'])->name('cierre.show');
        Route::get('cierre/{cierre}/edit', [CierreCajaController::class, 'edit'])->name('cierre.edit');
        Route::put('cierre/{cierre}', [CierreCajaController::class, 'update'])->name('cierre.update');
        Route::delete('cierre/{cierre}', [CierreCajaController::class, 'destroy'])->name('cierre.destroy');

        // Configuración de Empresa
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');

        // Eventos / Auditoría
        Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
        Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

        // Usuarios (admin y técnico pueden gestionar; anular es solo admin)
        Route::resource('usuarios', UserController::class)->except(['destroy']);
        Route::post('usuarios/{usuario}/change-password', [UserController::class, 'changePassword'])->middleware('throttle:10,1')->name('usuarios.change-password');
    });

    // ─── INVITADO: solo ver detalle y factura (desde búsqueda) ──────
    Route::middleware('role:admin,tecnico,invitado')->group(function () {
        Route::get('mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
        Route::get('mantenimientos/{mantenimiento}', [MantenimientoController::class, 'show'])->name('mantenimientos.show');
        Route::get('mantenimientos/{mantenimiento}/factura', [MantenimientoController::class, 'factura'])->name('mantenimientos.factura');

        Route::get('electronicas', [ElectronicaController::class, 'index'])->name('electronicas.index');
        Route::get('electronicas/{electronica}', [ElectronicaController::class, 'show'])->name('electronicas.show');
        Route::get('electronicas/{electronica}/factura', [ElectronicaController::class, 'factura'])->name('electronicas.factura');
    });

    // ─── Admin y Técnico: anular y bajas (técnico requiere contraseña de admin) ─────
    Route::middleware(['role:admin,tecnico', 'throttle:10,1'])->group(function () {
        Route::post('clientes/{cliente}/anular', [ClienteController::class, 'anular'])->name('clientes.anular');
        Route::post('equipos/{equipo}/anular', [EquipoController::class, 'anular'])->name('equipos.anular');
        Route::post('equipos/{equipo}/dar-de-baja', [EquipoController::class, 'darDeBaja'])->name('equipos.dar-de-baja');
        Route::post('equipos/{equipo}/reactivar', [EquipoController::class, 'reactivar'])->name('equipos.reactivar');
        Route::post('tecnicos/{tecnico}/anular', [TecnicoController::class, 'anular'])->name('tecnicos.anular');
        Route::post('stocks/{stock}/anular', [StockController::class, 'anular'])->name('stocks.anular');
        Route::post('stocks/{stock}/dar-de-baja', [StockController::class, 'darDeBaja'])->name('stocks.dar-de-baja');
        Route::post('stocks/bajas/{bajaStock}/revertir', [StockController::class, 'revertirBaja'])->name('stocks.bajas.revertir');
        Route::post('electronicas/{electronica}/anular', [ElectronicaController::class, 'anular'])->name('electronicas.anular');
        Route::post('mantenimientos/{mantenimiento}/anular', [MantenimientoController::class, 'anular'])->name('mantenimientos.anular');
        Route::post('proveedores/{proveedor}/anular', [ProveedorController::class, 'anular'])->name('proveedores.anular');
        Route::post('caja/{movimiento}/anular', [MovimientoCajaController::class, 'anular'])->name('caja.anular');
        Route::post('inventario/facturas/{factura}/anular', [MovimientoInventarioController::class, 'anularFactura'])->name('inventario.facturas.anular');
        Route::post('usuarios/{usuario}/anular', [UserController::class, 'anular'])->name('usuarios.anular');
    });
});
