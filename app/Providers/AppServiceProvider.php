<?php

namespace App\Providers;

use App\Database\SqliteConnector;
use App\Models\Abono;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Evento;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Stock;
use App\Models\User;
use App\Policies\CierreCajaPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CotizacionPolicy;
use App\Policies\ElectronicaPolicy;
use App\Policies\FacturaPolicy;
use App\Policies\MantenimientoPolicy;
use App\Policies\MovimientoCajaPolicy;
use App\Policies\StockPolicy;
use App\Policies\UserPolicy;
use App\Support\PaginacionPdf;
use App\View\Composers\NotificacionesComposer;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // SQLite no implementa GREATEST() (columnas generadas de facturas).
        $this->app->bind('db.connector.sqlite', SqliteConnector::class);

        // Numeración de páginas de los PDF sin PHP embebido (ver App\Support\PaginacionPdf).
        $this->app->extend('dompdf', fn (Dompdf $dompdf) => PaginacionPdf::registrar($dompdf));

        // Dusk expone /_dusk/login/{id}: solo en local o testing, nunca en producción.
        if ($this->app->environment('local', 'testing') && filter_var(env('DUSK_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->app->register(DuskServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.utf8', 'es_ES', 'spanish', 'es');

        // isSecure() ya considera X-Forwarded-Proto, pero solo de los proxies de confianza
        // (bootstrap/app.php); leer la cabecera cruda permitía que cualquiera la falsificara.
        if (request()->isSecure()) {
            URL::forceScheme('https');
        }

        // ─────────────────────────────────────────────────────────────────
        // GATES DE AUTORIZACIÓN CENTRALIZADOS
        // ─────────────────────────────────────────────────────────────────
        Gate::define('promote-admin', fn (User $u) => $u->role === 'admin');
        Gate::define('promote-tecnico', fn (User $u) => $u->role === 'admin');
        Gate::define('manage-caja', fn (User $u) => ! $u->isInvitado());
        Gate::define('close-caja', fn (User $u) => ! $u->isInvitado());
        Gate::define('manage-users', fn (User $u) => $u->isAdmin());
        Gate::define('manage-backups', fn (User $u) => $u->isAdmin());
        Gate::define('manage-config', fn (User $u) => $u->isAdmin());
        Gate::define('view-audit', fn (User $u) => $u->isAdmin());
        // ARQ-01 / S-11: los permisos se aplican en las rutas (middleware `can:`) y coinciden
        // con lo que hace la aplicación. Anular o borrar no es un permiso fijo por rol: el
        // técnico puede hacerlo con la clave de un administrador (AnulacionService). Los
        // reportes financieros los usan admin y técnico (el técnico opera la caja y el cierre).

        Gate::policy(Mantenimiento::class, MantenimientoPolicy::class);
        Gate::policy(Electronica::class, ElectronicaPolicy::class);
        Gate::policy(Factura::class, FacturaPolicy::class);
        Gate::policy(Cotizacion::class, CotizacionPolicy::class);
        Gate::policy(CierreCaja::class, CierreCajaPolicy::class);
        Gate::policy(MovimientoCaja::class, MovimientoCajaPolicy::class);
        Gate::policy(Stock::class, StockPolicy::class);
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Notificaciones de la barra superior (ARQ-06): la lógica vive en su propia clase y la
        // caché compartida se borra cuando cambia algo que las afecta.
        View::composer('layouts.app', NotificacionesComposer::class);
        foreach ([Mantenimiento::class, Electronica::class, Factura::class, Cotizacion::class, MovimientoCaja::class, Abono::class] as $modelo) {
            $modelo::saved(fn () => NotificacionesComposer::invalidar());
            $modelo::deleted(fn () => NotificacionesComposer::invalidar());
        }

        Event::listen(Login::class, function ($event) {
            $history = session()->pull('login_attempts_history', []);
            $totalFallidos = count($history);

            if ($totalFallidos > 0) {
                $viejos = [
                    'Total Intentos Fallidos' => $totalFallidos,
                ];
                foreach ($history as $h) {
                    $viejos["Intento #{$h['intento']}"] = "❌ {$h['motivo']} a las {$h['hora']} (IP: {$h['ip']})";
                }

                $nuevos = [
                    'Resultado' => '✅ Inicio de sesión exitoso',
                    'Total Intentos Requeridos' => $totalFallidos + 1,
                    'Hora de Conexión' => now()->format('d/m/Y H:i:s'),
                    'Dirección IP' => request()->ip(),
                    'Navegador' => substr(request()->userAgent() ?? 'N/D', 0, 255),
                ];
                $descripcion = "El usuario inició sesión tras {$totalFallidos} ".($totalFallidos === 1 ? 'intento fallido' : 'intentos fallidos').'.';
            } else {
                $viejos = null;
                $nuevos = null;
                $descripcion = 'El usuario inició sesión en el sistema al primer intento.';
            }

            Evento::registrar('login', $event->user, $viejos, $nuevos, $descripcion);
        });

        Event::listen(Logout::class, function ($event) {
            if ($event->user) {
                Evento::registrar('logout', $event->user, null, null, 'El usuario cerró sesión en el sistema.');
            }
        });
    }
}
