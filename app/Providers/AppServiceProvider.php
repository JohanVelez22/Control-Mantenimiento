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
            $mantList = \App\Models\Mantenimiento::where('estado', 'pendiente')
                            ->select('id', 'numero_orden', 'dispositivo', 'estado')
                            ->with('cliente:id,nombre')
                            ->latest()
                            ->get();

            $elecList = \App\Models\Electronica::where('estado', 'pendiente')
                            ->select('id', 'numero_orden', 'dispositivo', 'estado')
                            ->with('cliente:id,nombre')
                            ->latest()
                            ->get();

            $cajaList = \App\Models\Factura::where('estado', '!=', 'anulado')
                            ->where('saldo_pendiente', '>', 0)
                            ->select('id', 'numero', 'tipo', 'saldo_pendiente', 'total')
                            ->with('cliente:id,nombre')
                            ->latest()
                            ->get();

            $view->with([
                'mantList'        => $mantList,
                'elecList'        => $elecList,
                'cajaList'        => $cajaList,
                'mantPendientes'  => $mantList->count(),
                'elecPendientes'  => $elecList->count(),
                'cajaPendientes'  => $cajaList->count(),
                'totalPendientes' => $mantList->count() + $elecList->count() + $cajaList->count(),
            ]);
        });
    }
}
