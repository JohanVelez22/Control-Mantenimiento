<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\MovimientoCaja;

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

        View::composer('layouts.app', function ($view) {
            // ─────────────────────────────────────────────────────────────────
            // Las notificaciones se consultan en tiempo real para evitar 
            // problemas de serialización (__PHP_Incomplete_Class) con la caché.
            // ─────────────────────────────────────────────────────────────────
            
            $mantList = Mantenimiento::activos()
                ->where('estado', 'pendiente')
                ->select('id', 'id_orden', 'equipo_id', 'estado')
                ->with('equipo.cliente:id,nombres,apellidos')
                ->latest()
                ->get();

            $elecList = Electronica::activos()
                ->where('estado', 'pendiente')
                ->select('id', 'id_orden', 'equipo_id', 'estado')
                ->with('equipo.cliente:id,nombres,apellidos')
                ->latest()
                ->get();

            $cajaList = Factura::where('estado', '!=', 'anulada')
                ->where('saldo_pendiente', '>', 0)
                ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                ->with('facturable')
                ->latest()
                ->get();

            $movimientosPendientes = MovimientoCaja::where('anulado', false)
                ->whereNull('parent_id')
                ->whereRaw('monto_total > monto')
                ->with(['concepto', 'childPayments'])
                ->latest()
                ->get()
                ->filter(function($m) {
                    return $m->saldo_pendiente > 0;
                })
                ->values();

            $data = compact('mantList', 'elecList', 'cajaList', 'movimientosPendientes');

            $view->with([
                'mantList'              => $data['mantList'],
                'elecList'              => $data['elecList'],
                'cajaList'              => $data['cajaList'],
                'movimientosPendientes' => $data['movimientosPendientes'],
                'mantPendientes'        => $data['mantList']->count(),
                'elecPendientes'        => $data['elecList']->count(),
                'cajaPendientes'        => $data['cajaList']->count() + $data['movimientosPendientes']->count(),
                'totalPendientes'       => $data['mantList']->count()
                                           + $data['elecList']->count()
                                           + $data['cajaList']->count()
                                           + $data['movimientosPendientes']->count(),
            ]);
        });

        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            \App\Models\Evento::registrar('login', $event->user, null, null, 'El usuario inició sesión en el sistema.');
        });

        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                \App\Models\Evento::registrar('logout', $event->user, null, null, 'El usuario cerró sesión en el sistema.');
            }
        });
    }
}
