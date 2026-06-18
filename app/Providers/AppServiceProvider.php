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
            
            // Ingresos/Egresos (Facturas) a los que les falta la totalidad del monto
            $cajaPendientes = \App\Models\Factura::where('estado', '!=', 'anulado')
                                ->where('saldo_pendiente', '>', 0)
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
