<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\MovimientoCaja;
use App\Models\Cotizacion;
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
            // Mantenimientos pendientes
            $mantList = Mantenimiento::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get();

                // Electrónicas pendientes - convertir a array simple
                $elecList = Electronica::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get();

                // Facturas con saldo pendiente - convertir a array simple
                $cajaList = Factura::where('estado', '!=', 'anulada')
                    ->where('saldo_pendiente', '>', 0)
                    ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                    ->with('facturable:id,tipo_entidad,nombre_razon_social,nombres,apellidos,identificacion')
                    ->latest()
                    ->limit(50)
                    ->get();

                // Movimientos de caja pendientes - convertir a array simple
                $movimientosPendientes = MovimientoCaja::where('anulado', false)
                    ->whereNull('parent_id')
                    ->whereRaw('monto_total > monto')
                    ->with(['concepto:id,nombre', 'childPayments'])
                    ->latest()
                    ->limit(50)
                    ->get();

                // Cotizaciones pendientes - convertir a array simple
                $cotList = Cotizacion::where('estado', 'pendiente')
                    ->select('id', 'codigo', 'cliente_id', 'total', 'fecha')
                    ->with('cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get();

            $view->with([
                'mantList'              => $mantList,
                'elecList'              => $elecList,
                'cajaList'              => $cajaList,
                'movimientosPendientes' => $movimientosPendientes,
                'cotList'               => $cotList,
                'mantPendientes'        => $mantList->count(),
                'elecPendientes'        => $elecList->count(),
                'cotPendientes'         => $cotList->count(),
                'cajaPendientes'        => $cajaList->count() + $movimientosPendientes->count(),
                'totalPendientes'       => $mantList->count()
                                              + $elecList->count()
                                              + $cajaList->count()
                                              + $movimientosPendientes->count()
                                              + $cotList->count(),
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