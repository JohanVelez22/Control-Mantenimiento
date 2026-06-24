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
        if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $mantList = \App\Models\Mantenimiento::where('anulado', false)
                            ->where('estado', 'pendiente')
                            ->select('id', 'id_orden', 'equipo_id', 'estado')
                            ->with('equipo.cliente:id,nombre')
                            ->latest()
                            ->get();

            $elecList = \App\Models\Electronica::where('anulado', false)
                            ->where('estado', 'pendiente')
                            ->select('id', 'id_orden', 'equipo_id', 'estado')
                            ->with('equipo.cliente:id,nombre')
                            ->latest()
                            ->get();

            $cajaList = \App\Models\Factura::where('estado', '!=', 'anulada')
                            ->where('saldo_pendiente', '>', 0)
                            ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                            ->with('facturable')
                            ->latest()
                            ->get();

            $movimientosPendientes = \App\Models\MovimientoCaja::where('anulado', false)
                            ->whereRaw('monto_total > monto')
                            ->with('concepto')
                            ->latest()
                            ->get();

            $view->with([
                'mantList'              => $mantList,
                'elecList'              => $elecList,
                'cajaList'              => $cajaList,
                'movimientosPendientes' => $movimientosPendientes,
                'mantPendientes'        => $mantList->count(),
                'elecPendientes'        => $elecList->count(),
                'cajaPendientes'        => $cajaList->count() + $movimientosPendientes->count(),
                'totalPendientes'       => $mantList->count() + $elecList->count() + $cajaList->count() + $movimientosPendientes->count(),
            ]);
        });
    }
}
