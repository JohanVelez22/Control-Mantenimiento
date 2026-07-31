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
        \Carbon\Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.utf8', 'es_ES', 'spanish', 'es');

        if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Directiva Blade para formato de moneda uniforme ($1.000.000)
        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format(($expression) ?? 0, 0, ',', '.'); ?>";
        });

        // ─────────────────────────────────────────────────────────────────
        // GATES DE AUTORIZACIÓN CENTRALIZADOS
        // ─────────────────────────────────────────────────────────────────
        Gate::define('promote-admin', fn(User $u) => $u->role === 'admin');
        Gate::define('promote-tecnico', fn(User $u) => $u->role === 'admin');

        Gate::policy(Mantenimiento::class, \App\Policies\MantenimientoPolicy::class);
        Gate::policy(Electronica::class, \App\Policies\ElectronicaPolicy::class);


        View::composer('layouts.app', function ($view) {
            // Mantenimientos pendientes
            $mantList = Mantenimiento::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->get();

            // Electrónicas pendientes
            $elecList = Electronica::activos()
                ->where('estado', 'pendiente')
                ->select('id', 'id_orden', 'equipo_id', 'estado')
                ->with('equipo.cliente:id,nombres,apellidos')
                ->latest()
                ->limit(50)
                ->get();

            // Facturas con saldo pendiente (Compras / Ventas)
            $cajaList = Factura::where('estado', '!=', 'anulada')
                ->where('saldo_pendiente', '>', 0)
                ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                ->with('facturable')
                ->latest()
                ->limit(50)
                ->get();

            // Extraer números de factura pendientes para evitar duplicar en notificaciones de caja
            $facturasNumeros = $cajaList->pluck('numero_factura')->filter()->toArray();

            // Movimientos de caja pendientes independientes (excluye facturas de inventario y movimientos cuyo saldo ya fue saldado con abonos)
            $movimientosPendientes = MovimientoCaja::where('anulado', false)
                ->whereNull('parent_id')
                ->whereNotNull('monto_total')
                ->where('monto_total', '>', 0)
                ->when(!empty($facturasNumeros), function ($query) use ($facturasNumeros) {
                    $query->where(function ($q) use ($facturasNumeros) {
                        foreach ($facturasNumeros as $num) {
                            $q->where('descripcion', 'not like', "%#{$num}%");
                        }
                    });
                })
                ->with(['concepto:id,nombre', 'childPayments'])
                ->latest()
                ->get()
                ->filter(fn($mov) => $mov->saldo_pendiente > 0.01)
                ->take(50);

            // Cotizaciones pendientes
            $cotList = Cotizacion::activos()
                ->where('estado', 'pendiente')
                ->select('id', 'codigo', 'cliente_id', 'total', 'estado')
                ->with('cliente:id,nombres,apellidos')
                ->latest()
                ->limit(50)
                ->get();

            $data = [
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
            ];

            $view->with($data);
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