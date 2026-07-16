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
            // Cache por 30 segundos para evitar sobrecargar la BD en cada carga de página
            $data = Cache::remember('layout.notifications', 30, function () {
                // Mantenimientos pendientes - convertir a array simple para evitar problemas de serialización
                $mantList = Mantenimiento::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function($m) {
                        return [
                            'id' => $m->id,
                            'id_orden' => $m->id_orden,
                            'equipo_id' => $m->equipo_id,
                            'estado' => $m->estado,
                            'equipo' => [
                                'id' => $m->equipo->id ?? null,
                                'cliente' => [
                                    'id' => $m->equipo->cliente->id ?? null,
                                    'nombres' => $m->equipo->cliente->nombres ?? '',
                                    'apellidos' => $m->equipo->cliente->apellidos ?? '',
                                ]
                            ]
                        ];
                    })
                    ->all(); // Convert to plain array to avoid serialization issues

                // Electrónicas pendientes - convertir a array simple
                $elecList = Electronica::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function($e) {
                        return [
                            'id' => $e->id,
                            'id_orden' => $e->id_orden,
                            'equipo_id' => $e->equipo_id,
                            'estado' => $e->estado,
                            'equipo' => [
                                'id' => $e->equipo->id ?? null,
                                'cliente' => [
                                    'id' => $e->equipo->cliente->id ?? null,
                                    'nombres' => $e->equipo->cliente->nombres ?? '',
                                    'apellidos' => $e->equipo->cliente->apellidos ?? '',
                                ]
                            ]
                        ];
                    })
                    ->all(); // Convert to plain array to avoid serialization issues

                // Facturas con saldo pendiente - convertir a array simple
                $cajaList = Factura::where('estado', '!=', 'anulada')
                    ->where('saldo_pendiente', '>', 0)
                    ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                    ->with('facturable:id,tipo_entidad,nombre_razon_social,nombres,apellidos,identificacion')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function($f) {
                        return [
                            'id' => $f->id,
                            'numero_factura' => $f->numero_factura,
                            'tipo_movimiento' => $f->tipo_movimiento,
                            'saldo_pendiente' => $f->saldo_pendiente,
                            'total_documento' => $f->total_documento,
                            'facturable_id' => $f->facturable_id,
                            'facturable_type' => $f->facturable_type,
                            'facturable' => $f->facturable ? [
                                'id' => $f->facturable->id,
                                'tipo_entidad' => $f->facturable->tipo_entidad,
                                'nombre_razon_social' => $f->facturable->nombre_razon_social,
                                'nombres' => $f->facturable->nombres,
                                'apellidos' => $f->facturable->apellidos,
                                'identificacion' => $f->facturable->identificacion,
                            ] : null
                        ];
                    })
                    ->all(); // Convert to plain array to avoid serialization issues

                // Movimientos de caja pendientes - convertir a array simple
                $movimientosPendientes = MovimientoCaja::where('anulado', false)
                    ->whereNull('parent_id')
                    ->whereRaw('monto_total > monto')
                    ->with(['concepto:id,nombre', 'childPayments'])
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function($m) {
                        return [
                            'id' => $m->id,
                            'tipo_movimiento' => $m->tipo_movimiento,
                            'fecha' => $m->fecha,
                            'monto' => $m->monto,
                            'concepto' => $m->concepto ? [
                                'id' => $m->concepto->id,
                                'nombre' => $m->concepto->nombre,
                            ] : null,
                            'monto_total' => $m->monto_total,
                            'monto' => $m->monto,
                            'saldo_pendiente' => $m->saldo_pendiente,
                        ];
                    })
                    ->filter(function($m) {
                        return $m['saldo_pendiente'] > 0;
                    })
                    ->values()
                    ->all(); // Convert to plain array to avoid serialization issues

                // Cotizaciones pendientes - convertir a array simple
                $cotList = Cotizacion::where('estado', 'pendiente')
                    ->select('id', 'codigo', 'cliente_id', 'total', 'fecha')
                    ->with('cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function($c) {
                        return [
                            'id' => $c->id,
                            'codigo' => $c->codigo,
                            'total' => $c->total,
                            'fecha' => $c->fecha,
                            'cliente' => $c->cliente ? [
                                'id' => $c->cliente->id,
                                'nombre' => $c->cliente->nombres . ' ' . $c->cliente->apellidos,
                            ] : null,
                        ];
                    })
                    ->all();

                return compact('mantList', 'elecList', 'cajaList', 'movimientosPendientes', 'cotList');
            });

            $view->with([
                'mantList'              => $data['mantList'],
                'elecList'              => $data['elecList'],
                'cajaList'              => $data['cajaList'],
                'movimientosPendientes' => $data['movimientosPendientes'],
                'cotList'               => $data['cotList'],
                'mantPendientes'        => count($data['mantList']),
                'elecPendientes'        => count($data['elecList']),
                'cotPendientes'         => count($data['cotList']),
                'cajaPendientes'        => count($data['cajaList']) + count($data['movimientosPendientes']),
                'totalPendientes'       => count($data['mantList'])
                                              + count($data['elecList'])
                                              + count($data['cajaList'])
                                              + count($data['movimientosPendientes'])
                                              + count($data['cotList']),
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