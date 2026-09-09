<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
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

        // Garantizar existencia de formato_factura en configuraciones (Riesgo Cero)
        if (!Cache::has('schema_config_formato_checked')) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('configuraciones') && !\Illuminate\Support\Facades\Schema::hasColumn('configuraciones', 'formato_factura')) {
                    \Illuminate\Support\Facades\Schema::table('configuraciones', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('formato_factura')->default('estandar')->after('pie_pagina_factura');
                    });
                }
                Cache::forever('schema_config_formato_checked', true);
            } catch (\Throwable $e) {
                // Fallback silencioso
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // GATES DE AUTORIZACIÓN CENTRALIZADOS
        // ─────────────────────────────────────────────────────────────────
        Gate::define('promote-admin', fn(User $u) => $u->role === 'admin');
        Gate::define('promote-tecnico', fn(User $u) => $u->role === 'admin');

        Gate::policy(Mantenimiento::class, \App\Policies\MantenimientoPolicy::class);
        Gate::policy(Electronica::class, \App\Policies\ElectronicaPolicy::class);


        View::composer('layouts.app', function ($view) {
            if (!Auth::check()) {
                $view->with([
                    'mantList'              => collect(),
                    'elecList'              => collect(),
                    'cajaList'              => collect(),
                    'movimientosPendientes' => collect(),
                    'cotList'               => collect(),
                    'mantPendientes'        => 0,
                    'elecPendientes'        => 0,
                    'cotPendientes'         => 0,
                    'cajaPendientes'        => 0,
                    'totalPendientes'       => 0,
                ]);
                return;
            }

            $userId = Auth::id();
            $data = Cache::remember("topbar_notifs_user_{$userId}", 30, function () {
                
                // Mantenimientos pendientes
                $mantList = Mantenimiento::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function ($m) {
                        $clienteNombre = is_object($m->equipo?->cliente)
                            ? trim(($m->equipo->cliente->nombres ?? '') . ' ' . ($m->equipo->cliente->apellidos ?? ''))
                            : '—';
                        return [
                            'id'             => $m->id,
                            'id_orden'       => $m->id_orden,
                            'equipo_nombre'  => $m->equipo?->nombre ?? 'N/A',
                            'cliente_nombre' => $clienteNombre ?: '—',
                            'url'            => route('mantenimientos.show', $m->id),
                        ];
                    })->values()->all();

                // Electrónicas pendientes
                $elecList = Electronica::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'id_orden', 'equipo_id', 'estado')
                    ->with('equipo.cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function ($e) {
                        $clienteNombre = is_object($e->equipo?->cliente)
                            ? trim(($e->equipo->cliente->nombres ?? '') . ' ' . ($e->equipo->cliente->apellidos ?? ''))
                            : '—';
                        return [
                            'id'             => $e->id,
                            'id_orden'       => $e->id_orden,
                            'equipo_nombre'  => $e->equipo?->nombre ?? 'N/A',
                            'cliente_nombre' => $clienteNombre ?: '—',
                            'url'            => route('electronicas.show', $e->id),
                        ];
                    })->values()->all();

                // Facturas con saldo pendiente (Compras / Ventas)
                $cajaFacturas = Factura::where('estado', '!=', 'anulada')
                    ->where('saldo_pendiente', '>', 0)
                    ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
                    ->with('facturable')
                    ->latest()
                    ->limit(50)
                    ->get();

                // Extraer números de factura pendientes para búsqueda en lote (evita N+1 queries)
                $facturasNumeros = $cajaFacturas->pluck('numero_factura')->filter()->values()->toArray();

                // Movimientos de caja relacionados en lote
                $movimientosRel = empty($facturasNumeros) ? collect() : MovimientoCaja::whereNull('parent_id')
                    ->where(function ($q) use ($facturasNumeros) {
                        foreach ($facturasNumeros as $num) {
                            $q->orWhere('descripcion', 'like', "%#{$num}%");
                        }
                    })
                    ->select('id', 'descripcion')
                    ->get();

                $cajaList = $cajaFacturas->map(function ($f) use ($movimientosRel) {
                    $matched = $movimientosRel->first(function ($m) use ($f) {
                        return str_contains($m->descripcion ?? '', "#{$f->numero_factura}");
                    });
                    $movCajaId = $matched?->id;
                    $facturableNombre = is_object($f->facturable)
                        ? ($f->facturable->nombre_razon_social ?? $f->facturable->nombre ?? '—')
                        : '—';
                    return [
                        'id'                => $f->id,
                        'numero_factura'    => $f->numero_factura,
                        'facturable_nombre' => $facturableNombre ?: '—',
                        'saldo_pendiente'   => (float) $f->saldo_pendiente,
                        'movimiento_caja_id'=> $movCajaId,
                        'url'               => $movCajaId ? route('caja.edit', $movCajaId) : route('inventario.facturas.show', $f->id),
                    ];
                })->values()->all();

                // Movimientos de caja pendientes independientes (excluye facturas de inventario y movimientos ya saldados)
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
                    ->limit(50)
                    ->get()
                    ->filter(fn($mov) => $mov->saldo_pendiente > 0.01)
                    ->take(50)
                    ->map(function ($mov) {
                        $conceptoNombre = is_object($mov->concepto) ? $mov->concepto->nombre : ($mov->concepto ?? '—');
                        return [
                            'id'              => $mov->id,
                            'tipo_movimiento' => $mov->tipo_movimiento,
                            'concepto_nombre' => $conceptoNombre ?: '—',
                            'persona'         => $mov->persona ?? '—',
                            'saldo_pendiente' => (float) $mov->saldo_pendiente,
                            'url'             => route('caja.edit', $mov->id),
                        ];
                    })->values()->all();

                // Cotizaciones pendientes
                $cotList = Cotizacion::activos()
                    ->where('estado', 'pendiente')
                    ->select('id', 'codigo', 'cliente_id', 'total', 'estado')
                    ->with('cliente:id,nombres,apellidos')
                    ->latest()
                    ->limit(50)
                    ->get()
                    ->map(function ($c) {
                        $clienteNombre = is_object($c->cliente)
                            ? trim(($c->cliente->nombres ?? '') . ' ' . ($c->cliente->apellidos ?? ''))
                            : 'N/A';
                        return [
                            'id'             => $c->id,
                            'codigo'         => $c->codigo ?? '—',
                            'cliente_nombre' => $clienteNombre ?: '—',
                            'total'          => (float) ($c->total ?? 0),
                            'url'            => route('cotizaciones.show', $c->id),
                        ];
                    })->values()->all();

                return [
                    'mantList'              => $mantList,
                    'elecList'              => $elecList,
                    'cajaList'              => $cajaList,
                    'movimientosPendientes' => $movimientosPendientes,
                    'cotList'               => $cotList,
                    'mantPendientes'        => count($mantList),
                    'elecPendientes'        => count($elecList),
                    'cotPendientes'         => count($cotList),
                    'cajaPendientes'        => count($cajaList) + count($movimientosPendientes),
                    'totalPendientes'       => count($mantList)
                                              + count($elecList)
                                              + count($cajaList)
                                              + count($movimientosPendientes)
                                              + count($cotList),
                ];
            });

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