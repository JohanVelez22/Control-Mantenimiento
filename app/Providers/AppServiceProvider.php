<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $mantPendientes = \App\Models\Mantenimiento::where('estado', 'pendiente')->count();
            $elecPendientes = \App\Models\Electronica::where('estado', 'pendiente')->count();
            
            // Movimientos de caja activos donde el monto total sea mayor al monto pagado
            $cajaPendientes = \App\Models\MovimientoCaja::where('estado', 'activo')
                                ->whereNotNull('monto_total')
                                ->whereRaw('monto_total > monto')
                                ->count();
            
            $view->with([
                'mantPendientes' => $mantPendientes,
                'elecPendientes' => $elecPendientes,
                'cajaPendientes' => $cajaPendientes,
                'totalPendientes' => $mantPendientes + $elecPendientes + $cajaPendientes
            ]);
        });
    }
}
