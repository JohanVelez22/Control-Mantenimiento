<?php

namespace App\View\Composers;

use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Notificaciones de la barra superior (campana y modal), ARQ-06 / F-05 / B-22.
 *
 * - Los contadores son conteos reales (antes eran el tamaño de una lista con tope de 50).
 * - Las listas muestran los 50 más recientes de cada categoría.
 * - Los movimientos de caja con saldo se filtran en SQL antes de limitar (antes se
 *   tomaban 50 y luego se filtraban, y los pendientes más antiguos no aparecían).
 * - Los pagos se enlazan solo por clave (D-05), sin buscar referencias en el texto.
 * - Una sola copia en caché para todos los usuarios con acceso, que se borra cuando
 *   cambia una orden, factura, cotización o movimiento (ver invalidar()).
 * - "Atrasadas": órdenes pendientes con más de DIAS_ATRASO días y cotizaciones vencidas.
 */
class NotificacionesComposer
{
    public const CLAVE_CACHE = 'topbar_notificaciones';

    public const LIMITE_LISTA = 50;

    public const DIAS_ATRASO = 3;

    public static function invalidar(): void
    {
        Cache::forget(self::CLAVE_CACHE);
    }

    public function compose(View $view): void
    {
        // Solo admin y técnico ven notificaciones: el invitado (cuenta compartida) nunca debe
        // recibir nombres de clientes ni saldos, ni siquiera en páginas de error (F-01).
        if (! Auth::check() || ! in_array(Auth::user()->role, ['admin', 'tecnico'], true)) {
            $view->with(self::vacio());

            return;
        }

        $view->with(Cache::remember(self::CLAVE_CACHE, 60, fn () => $this->calcular()));
    }

    public static function vacio(): array
    {
        return [
            'mantList' => [], 'elecList' => [], 'cajaList' => [], 'movimientosPendientes' => [], 'cotList' => [],
            'mantPendientes' => 0, 'elecPendientes' => 0, 'cotPendientes' => 0, 'cajaPendientes' => 0,
            'totalPendientes' => 0, 'atrasadas' => 0,
        ];
    }

    private function calcular(): array
    {
        $limiteAtraso = now()->subDays(self::DIAS_ATRASO)->toDateString();

        $ordenes = function (string $modelo, string $ruta) use ($limiteAtraso) {
            $base = $modelo::activos()->where('estado', 'pendiente');

            $lista = (clone $base)
                ->select('id', 'id_orden', 'equipo_id', 'estado', 'fecha_entrada')
                ->with(['equipo.cliente:id,nombres,apellidos', 'equipo.proveedor:id,nombre_razon_social'])
                ->orderByDesc('id')
                ->limit(self::LIMITE_LISTA)
                ->get()
                ->map(fn ($o) => [
                    'id' => $o->id,
                    'id_orden' => $o->id_orden,
                    'equipo_nombre' => $o->equipo?->nombre ?? 'N/A',
                    'cliente_nombre' => $o->equipo?->propietario_nombre ?: '—',
                    'atrasada' => $o->fecha_entrada && $o->fecha_entrada->toDateString() < $limiteAtraso,
                    'url' => route($ruta, $o->id),
                ])->values()->all();

            return [$lista, (clone $base)->count(), (clone $base)->where('fecha_entrada', '<', $limiteAtraso)->count()];
        };

        [$mantList, $mantTotal, $mantAtrasadas] = $ordenes(Mantenimiento::class, 'mantenimientos.show');
        [$elecList, $elecTotal, $elecAtrasadas] = $ordenes(Electronica::class, 'electronicas.show');

        // Facturas con saldo pendiente (compras y ventas)
        $facturasBase = Factura::where('estado', '!=', 'anulada')->where('saldo_pendiente', '>', 0.01);
        $facturas = (clone $facturasBase)
            ->select('id', 'numero_factura', 'tipo_movimiento', 'saldo_pendiente', 'total_documento', 'facturable_id', 'facturable_type')
            ->with('facturable')
            ->orderByDesc('id')
            ->limit(self::LIMITE_LISTA)
            ->get();
        $pagoBase = MovimientoCaja::whereNull('parent_id')
            ->whereIn('factura_id', $facturas->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'factura_id'])
            ->unique('factura_id')
            ->keyBy('factura_id');

        $cajaList = $facturas->map(function ($f) use ($pagoBase) {
            $movId = $pagoBase->get($f->id)?->id;
            $nombre = is_object($f->facturable) ? ($f->facturable->nombre_razon_social ?? $f->facturable->nombre ?? '—') : '—';

            return [
                'id' => $f->id,
                'numero_factura' => $f->numero_factura,
                'facturable_nombre' => $nombre ?: '—',
                'saldo_pendiente' => (float) $f->saldo_pendiente,
                'movimiento_caja_id' => $movId,
                'url' => $movId ? route('caja.edit', $movId) : route('inventario.facturas.show', $f->id),
            ];
        })->values()->all();

        // Movimientos de caja con saldo (cuentas por cobrar o pagar registradas en caja)
        $movBase = MovimientoCaja::where('anulado', false)
            ->whereNull('parent_id')
            ->whereNull('factura_id')
            ->whereNull('abono_id')
            ->where('monto_total', '>', 0)
            ->whereRaw(
                "monto_total - (CASE WHEN estado = 'activo' THEN monto ELSE 0 END) - COALESCE((SELECT SUM(c.monto) FROM movimiento_cajas c WHERE c.parent_id = movimiento_cajas.id AND c.anulado = ? AND c.estado = 'activo'), 0) > 0.01",
                [false]
            );
        $movimientosPendientes = (clone $movBase)
            ->with(['concepto:id,nombre', 'childPayments'])
            ->orderByDesc('id')
            ->limit(self::LIMITE_LISTA)
            ->get()
            ->map(fn ($mov) => [
                'id' => $mov->id,
                'tipo_movimiento' => $mov->tipo_movimiento,
                'concepto_nombre' => (is_object($mov->concepto) ? $mov->concepto->nombre : null) ?: '—',
                'persona' => $mov->persona ?? '—',
                'saldo_pendiente' => (float) $mov->saldo_pendiente,
                'url' => route('caja.edit', $mov->id),
            ])->values()->all();

        // Cotizaciones pendientes; las vencidas cuentan como atrasadas.
        $cotBase = Cotizacion::activos()->where('estado', 'pendiente');
        $cotizaciones = (clone $cotBase)
            ->select('id', 'codigo', 'cliente_id', 'proveedor_id', 'total', 'estado', 'fecha', 'validez_dias')
            ->with(['cliente:id,nombres,apellidos', 'proveedor:id,nombre_razon_social'])
            ->orderByDesc('id')
            ->limit(self::LIMITE_LISTA)
            ->get();
        $cotList = $cotizaciones->map(fn ($c) => [
            'id' => $c->id,
            'codigo' => $c->codigo ?? '—',
            'cliente_nombre' => $c->destinatario_nombre ?: '—',
            'total' => (float) ($c->total ?? 0),
            'atrasada' => self::cotizacionVencida($c),
            'url' => route('cotizaciones.show', $c->id),
        ])->values()->all();
        $cotTotal = (clone $cotBase)->count();
        $cotVencidas = (clone $cotBase)->get(['id', 'fecha', 'validez_dias'])->filter(fn ($c) => self::cotizacionVencida($c))->count();

        $cajaTotal = (clone $facturasBase)->count() + (clone $movBase)->count();

        return [
            'mantList' => $mantList,
            'elecList' => $elecList,
            'cajaList' => $cajaList,
            'movimientosPendientes' => $movimientosPendientes,
            'cotList' => $cotList,
            'mantPendientes' => $mantTotal,
            'elecPendientes' => $elecTotal,
            'cotPendientes' => $cotTotal,
            'cajaPendientes' => $cajaTotal,
            'totalPendientes' => $mantTotal + $elecTotal + $cajaTotal + $cotTotal,
            'atrasadas' => $mantAtrasadas + $elecAtrasadas + $cotVencidas,
        ];
    }

    private static function cotizacionVencida($cotizacion): bool
    {
        if (! $cotizacion->fecha || ! $cotizacion->validez_dias) {
            return false;
        }

        return Carbon::parse($cotizacion->fecha)->addDays((int) $cotizacion->validez_dias)->endOfDay()->isPast();
    }
}
