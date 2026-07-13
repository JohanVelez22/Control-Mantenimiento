<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Models\Stock;
use App\Models\ConceptoCaja;
use App\Models\CategoriaStock;
use App\Models\CierreCaja;
use App\Policies\MantenimientoPolicy;
use App\Policies\ElectronicaPolicy;
use App\Policies\FacturaPolicy;
use App\Policies\MovimientoCajaPolicy;
use App\Policies\StockPolicy;
use App\Policies\ConceptoCajaPolicy;
use App\Policies\CategoriaStockPolicy;
use App\Policies\CierreCajaPolicy;
use App\Policies\UserPolicy;

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

        // ─────────────────────────────────────────────────────────────────
        // GATES DE AUTORIZACIÓN CENTRALIZADOS
        // ─────────────────────────────────────────────────────────────────
        Gate::define('promote-admin', fn(User $u) => $u->role === 'admin');
        Gate::define('promote-tecnico', fn(User $u) => $u->role === 'admin');

        // Políticas por modelo
        Gate::policy(Mantenimiento::class, MantenimientoPolicy::class);
        Gate::policy(Electronica::class, ElectronicaPolicy::class);
        Gate::policy(Factura::class, FacturaPolicy::class);
        Gate::policy(MovimientoCaja::class, MovimientoCajaPolicy::class);
        Gate::policy(Stock::class, StockPolicy::class);
        Gate::policy(ConceptoCaja::class, ConceptoCajaPolicy::class);
        Gate::policy(CategoriaStock::class, CategoriaStockPolicy::class);
        Gate::policy(CierreCaja::class, CierreCajaPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

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
                ->limit(100)
                ->get();

            $elecList = Electronica::activos()
                ->where('estado', 'pendiente')
                ->select('id', 'id_orden', 'equipo_id', 'estado')
                ->with('equipo.cliente:id,nombres,apellidos')
                ->latest()
                ->limit(100)
                ->get();

            $cajaList = Factura::where('estado', '!=', 'anulada')
                ->where('saldo_pendiente', '>', 0)
                ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                ->with('facturable')
                ->latest()
                ->limit(100)
                ->get();

            $movimientosPendientes = MovimientoCaja::where('anulado', false)
                ->whereNull('parent_id')
                ->whereRaw('monto_total > monto')
                ->with(['concepto', 'childPayments'])
                ->latest()
                ->limit(100)
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
