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

// Rutas de invitados (no autenticados) — solo login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas (autenticados)
Route::middleware(['auth', \App\Http\Middleware\PreventBackHistory::class])->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Nueva Ruta de Reportes Independiente
    Route::get('/reportes', [MantenimientoController::class, 'reportes'])->name('mantenimientos.reportes');
    Route::get('/mantenimientos/{mantenimiento}/factura', [MantenimientoController::class, 'factura'])->name('mantenimientos.factura');

    // Módulos generales
    Route::resource('clientes', ClienteController::class);
    Route::resource('equipos', EquipoController::class);
    Route::resource('tecnicos', TecnicoController::class);
    Route::resource('mantenimientos', MantenimientoController::class);

    // Módulo de Usuarios (ADMIN y TÉCNICO)
    Route::middleware(['role:admin,tecnico'])->group(function () {
    Route::resource('usuarios', UserController::class);
    Route::post('usuarios/{usuario}/change-password', [UserController::class, 'changePassword'])->name('usuarios.change-password');
    });
});
