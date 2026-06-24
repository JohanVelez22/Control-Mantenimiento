<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\MovimientoCaja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReporteFinancieroController extends Controller
{
    // ═══════════════════════════════════════════════════════════════
    //  REPORTE DIARIO: Todos los movimientos de una fecha específica
    // ═══════════════════════════════════════════════════════════════

    public function reporteDiario(Request $request)
    {
        $fecha = $request->filled('fecha')
            ? Carbon::parse($request->fecha)->toDateString()
            : now()->toDateString();

        // — Mantenimientos ingresados ese día
        $mantenimientos = Mantenimiento::with(['equipo.cliente', 'tecnico'])
            ->whereDate('fecha_entrada', $fecha)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($m) {
                $equipo  = $m->equipo->nombre  ?? '—';
                $cliente = $m->equipo->cliente->nombre ?? '—';
                return [
                    'tipo'        => 'mantenimiento',
                    'fecha'       => $m->fecha_entrada,
                    'descripcion' => "{$m->id_orden} — {$equipo} ({$cliente})",
                    'monto'       => $m->costo,
                    'estado'      => $m->estado,
                    'anulado'     => $m->anulado,
                    'icono'       => '🔧',
                    'color'       => 'blue',
                ];
            });

        // — Electrónicas ingresadas ese día
        $electronicas = Electronica::with(['tecnico', 'equipo.cliente'])
            ->whereDate('fecha_entrada', $fecha)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($e) {
                $equipo  = $e->equipo->nombre  ?? '—';
                $cliente = $e->equipo->cliente->nombre ?? '—';
                return [
                    'tipo'        => 'electronica',
                    'fecha'       => $e->fecha_entrada,
                    'descripcion' => "{$e->id_orden} — {$equipo} ({$cliente})",
                    'monto'       => $e->costo,
                    'estado'      => $e->estado,
                    'anulado'     => $e->anulado,
                    'icono'       => '⚡',
                    'color'       => 'purple',
                ];
            });

        // — Facturas (compras y ventas)
        $facturas = Factura::with('facturable')
            ->whereDate('fecha', $fecha)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($f) {
                $nombre = $f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—';
                return [
                    'tipo'        => $f->tipo_movimiento,
                    'fecha'       => $f->fecha,
                    'descripcion' => "#{$f->numero_factura} — {$nombre}",
                    'monto'       => $f->total_documento,
                    'estado'      => $f->estado,
                    'anulado'     => $f->estado === 'anulada',
                    'icono'       => $f->tipo_movimiento === 'compra' ? '📦' : '🛒',
                    'color'       => $f->tipo_movimiento === 'compra' ? 'orange' : 'green',
                ];
            });

        // — Movimientos de Caja (ingresos/egresos)
        $caja = MovimientoCaja::with('concepto')
            ->whereDate('fecha', $fecha)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($c) {
                $quien    = $c->persona ?? $c->empresa ?? 'Anónimo';
                $concepto = $c->concepto->nombre ?? '—';
                return [
                    'tipo'        => $c->tipo_movimiento,
                    'fecha'       => $c->fecha,
                    'descripcion' => "{$quien} — {$concepto}",
                    'monto'       => $c->monto,
                    'estado'      => $c->estado,
                    'anulado'     => $c->anulado,
                    'icono'       => $c->tipo_movimiento === 'ingreso' ? '📈' : '📉',
                    'color'       => $c->tipo_movimiento === 'ingreso' ? 'emerald' : 'red',
                ];
            });

        // Unir y ordenar todos por fecha desc
        $movimientos = collect()
            ->merge($mantenimientos)
            ->merge($electronicas)
            ->merge($facturas)
            ->merge($caja)
            ->sortByDesc('fecha')
            ->values();

        $resumen = [
            'total_ingresos'       => $movimientos->where('anulado', false)->whereIn('tipo', ['ingreso', 'venta'])->sum('monto'),
            'total_egresos'        => $movimientos->where('anulado', false)->whereIn('tipo', ['egreso', 'compra'])->sum('monto'),
            'total_mantenimientos' => $mantenimientos->where('anulado', false)->sum('costo'),
            'total_anulados'       => $movimientos->where('anulado', true)->count(),
        ];

        if ($request->get('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ReportesFinancierosExport($movimientos),
                'Reporte_Diario_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        if ($request->get('export') === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes_financieros.pdf_diario', [
                'movimientos' => $movimientos,
                'resumen'     => $resumen,
                'fecha'       => \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM [de] YYYY'),
            ])->setPaper('a4', 'portrait')->download('Reporte_Diario_' . date('Y-m-d_His') . '.pdf');
        }

        return view('reportes_financieros.diario', compact('movimientos', 'fecha', 'resumen'));
    }

    // ═══════════════════════════════════════════════════════════════
    //  REPORTE ACUMULADO: Totales consolidados de un rango de fechas
    // ═══════════════════════════════════════════════════════════════

    public function reporteAcumulado(Request $request)
    {
        $desde = $request->filled('desde')
            ? Carbon::parse($request->desde)->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->filled('hasta')
            ? Carbon::parse($request->hasta)->endOfDay()
            : now()->endOfDay();

        // Mantenimientos
        $mantenimientosQuery = Mantenimiento::whereBetween('fecha_entrada', [$desde, $hasta])
            ->where('anulado', false);

        // Electrónicas
        $electronicasQuery = Electronica::whereBetween('fecha_entrada', [$desde, $hasta])
            ->where('anulado', false);

        // Movimientos de Caja
        $cajaBase = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->whereBetween('fecha', [$desde, $hasta]);

        // Facturas
        $facturasBase = Factura::where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$desde, $hasta]);

        $acumulado = [
            // Conteos
            'total_mantenimientos'  => (clone $mantenimientosQuery)->count(),
            'total_electronicas'    => (clone $electronicasQuery)->count(),
            'total_compras'         => (clone $facturasBase)->where('tipo_movimiento', 'compra')->count(),
            'total_ventas'          => (clone $facturasBase)->where('tipo_movimiento', 'venta')->count(),

            // Montos
            'facturado_mant'        => (clone $mantenimientosQuery)->sum('costo'),
            'facturado_elec'        => (clone $electronicasQuery)->sum('costo'),
            'ingresos_caja'         => (clone $cajaBase)->where('tipo_movimiento', 'ingreso')->sum('monto'),
            'egresos_caja'          => (clone $cajaBase)->where('tipo_movimiento', 'egreso')->sum('monto'),
            'ventas_inventario'     => (clone $facturasBase)->where('tipo_movimiento', 'venta')->sum('total_pagado'),
            'compras_inventario'    => (clone $facturasBase)->where('tipo_movimiento', 'compra')->sum('total_pagado'),

            // Pendientes
            'saldo_pendiente_venta' => (clone $facturasBase)->where('tipo_movimiento', 'venta')
                                        ->selectRaw('SUM(total_documento - total_pagado) as s')->value('s') ?? 0,
            'saldo_pendiente_compra'=> (clone $facturasBase)->where('tipo_movimiento', 'compra')
                                        ->selectRaw('SUM(total_documento - total_pagado) as s')->value('s') ?? 0,
        ];

        $acumulado['balance_neto'] = $acumulado['ingresos_caja'] - $acumulado['egresos_caja'];
        $acumulado['facturado_total'] = $acumulado['facturado_mant'] + $acumulado['facturado_elec'];

        // — Mantenimientos en el rango
        $mantenimientosList = Mantenimiento::with(['equipo.cliente', 'tecnico'])
            ->whereBetween('fecha_entrada', [$desde, $hasta])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($m) {
                $equipo  = $m->equipo->nombre  ?? '—';
                $cliente = $m->equipo->cliente->nombre ?? '—';
                return [
                    'tipo'        => 'mantenimiento',
                    'fecha'       => $m->fecha_entrada,
                    'descripcion' => "{$m->id_orden} — {$equipo} ({$cliente})",
                    'monto'       => $m->costo,
                    'estado'      => $m->estado,
                    'anulado'     => $m->anulado,
                    'icono'       => '🔧',
                    'color'       => 'blue',
                ];
            });

        // — Electrónicas en el rango
        $electronicasList = Electronica::with(['tecnico', 'equipo.cliente'])
            ->whereBetween('fecha_entrada', [$desde, $hasta])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($e) {
                $equipo  = $e->equipo->nombre  ?? '—';
                $cliente = $e->equipo->cliente->nombre ?? '—';
                return [
                    'tipo'        => 'electronica',
                    'fecha'       => $e->fecha_entrada,
                    'descripcion' => "{$e->id_orden} — {$equipo} ({$cliente})",
                    'monto'       => $e->costo,
                    'estado'      => $e->estado,
                    'anulado'     => $e->anulado,
                    'icono'       => '⚡',
                    'color'       => 'purple',
                ];
            });

        // — Facturas en el rango
        $facturasList = Factura::with('facturable')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($f) {
                $nombre = $f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—';
                return [
                    'tipo'        => $f->tipo_movimiento,
                    'fecha'       => $f->fecha,
                    'descripcion' => "#{$f->numero_factura} — {$nombre}",
                    'monto'       => $f->total_documento,
                    'estado'      => $f->estado,
                    'anulado'     => $f->estado === 'anulada',
                    'icono'       => $f->tipo_movimiento === 'compra' ? '📦' : '🛒',
                    'color'       => $f->tipo_movimiento === 'compra' ? 'orange' : 'green',
                ];
            });

        // — Movimientos de Caja en el rango
        $cajaList = MovimientoCaja::with('concepto')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($c) {
                $quien    = $c->persona ?? $c->empresa ?? 'Anónimo';
                $concepto = $c->concepto->nombre ?? '—';
                return [
                    'tipo'        => $c->tipo_movimiento,
                    'fecha'       => $c->fecha,
                    'descripcion' => "{$quien} — {$concepto}",
                    'monto'       => $c->monto,
                    'estado'      => $c->estado,
                    'anulado'     => $c->anulado,
                    'icono'       => $c->tipo_movimiento === 'ingreso' ? '📈' : '📉',
                    'color'       => $c->tipo_movimiento === 'ingreso' ? 'emerald' : 'red',
                ];
            });

        // Unir y ordenar todos por fecha desc
        $movimientos = collect()
            ->merge($mantenimientosList)
            ->merge($electronicasList)
            ->merge($facturasList)
            ->merge($cajaList)
            ->sortByDesc('fecha')
            ->values();

        if ($request->get('export') === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ReportesFinancierosExport($movimientos),
                'Reporte_Acumulado_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        if ($request->get('export') === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes_financieros.pdf_diario', [
                'movimientos' => $movimientos,
                'resumen' => [
                    'total_ingresos' => $acumulado['ingresos_caja'],
                    'total_egresos' => $acumulado['egresos_caja'],
                    'total_mantenimientos' => $acumulado['facturado_mant'],
                    'total_anulados' => $movimientos->where('anulado', true)->count(),
                ],
                'fecha' => "Del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')}"
            ])->setPaper('a4', 'portrait')->download('Reporte_Acumulado_' . date('Y-m-d_His') . '.pdf');
        }

        return view('reportes_financieros.acumulado', compact('acumulado', 'desde', 'hasta', 'movimientos'));
    }

    // ═══════════════════════════════════════════════════════════════
    //  REPORTE OPERACIONES: Filtro excluyente por tipo
    // ═══════════════════════════════════════════════════════════════

    public function reporteOperaciones(Request $request)
    {
        $tipo  = $request->get('tipo', 'solo_mantenimientos');
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta)->endOfDay() : now()->endOfDay();

        $registros = match ($tipo) {
            'solo_mantenimientos' => Mantenimiento::with(['equipo.cliente', 'tecnico', 'user'])
                ->whereBetween('fecha_entrada', [$desde, $hasta])
                ->orderBy('fecha_entrada', 'desc')
                ->paginate(10),

            'solo_electronica' => Electronica::with(['tecnico', 'user'])
                ->whereBetween('fecha_entrada', [$desde, $hasta])
                ->orderBy('fecha_entrada', 'desc')
                ->paginate(10),

            'solo_ingresos' => MovimientoCaja::with(['concepto', 'user'])
                ->where('anulado', false)
                ->where('tipo_movimiento', 'ingreso')
                ->whereBetween('fecha', [$desde, $hasta])
                ->orderBy('fecha', 'desc')
                ->paginate(10),

            'solo_egresos' => MovimientoCaja::with(['concepto', 'user'])
                ->where('anulado', false)
                ->where('tipo_movimiento', 'egreso')
                ->whereBetween('fecha', [$desde, $hasta])
                ->orderBy('fecha', 'desc')
                ->paginate(10),

            'solo_compras' => Factura::with(['facturable', 'items', 'user'])
                ->where('tipo_movimiento', 'compra')
                ->whereBetween('fecha', [$desde, $hasta])
                ->orderBy('fecha', 'desc')
                ->paginate(10),

            'solo_ventas' => Factura::with(['facturable', 'items', 'user'])
                ->where('tipo_movimiento', 'venta')
                ->whereBetween('fecha', [$desde, $hasta])
                ->orderBy('fecha', 'desc')
                ->paginate(10),

            default => collect()->paginate(10),
        };

        $tipoLabels = [
            'solo_mantenimientos' => '🔧 Mantenimientos',
            'solo_electronica'    => '⚡ Electrónica',
            'solo_ingresos'       => '📈 Ingresos de Caja',
            'solo_egresos'        => '📉 Egresos de Caja',
            'solo_compras'        => '📦 Compras de Inventario',
            'solo_ventas'         => '🛒 Ventas de Inventario',
        ];

        if ($request->get('export')) {
            // Obtener todos los registros sin paginación para exportar
            $exportData = match ($tipo) {
                'solo_mantenimientos' => Mantenimiento::with(['equipo.cliente', 'tecnico', 'user'])
                    ->whereBetween('fecha_entrada', [$desde, $hasta])
                    ->orderBy('fecha_entrada', 'desc')->get(),
                'solo_electronica' => Electronica::with(['tecnico', 'user'])
                    ->whereBetween('fecha_entrada', [$desde, $hasta])
                    ->orderBy('fecha_entrada', 'desc')->get(),
                'solo_ingresos' => MovimientoCaja::with(['concepto', 'user'])
                    ->where('tipo_movimiento', 'ingreso')
                    ->whereBetween('fecha', [$desde, $hasta])
                    ->orderBy('fecha', 'desc')->get(),
                'solo_egresos' => MovimientoCaja::with(['concepto', 'user'])
                    ->where('tipo_movimiento', 'egreso')
                    ->whereBetween('fecha', [$desde, $hasta])
                    ->orderBy('fecha', 'desc')->get(),
                'solo_compras' => Factura::with(['facturable', 'items', 'user'])
                    ->where('tipo_movimiento', 'compra')
                    ->whereBetween('fecha', [$desde, $hasta])
                    ->orderBy('fecha', 'desc')->get(),
                'solo_ventas' => Factura::with(['facturable', 'items', 'user'])
                    ->where('tipo_movimiento', 'venta')
                    ->whereBetween('fecha', [$desde, $hasta])
                    ->orderBy('fecha', 'desc')->get(),
                default => collect(),
            };

            if ($request->get('export') === 'excel') {
                if ($tipo === 'solo_mantenimientos') {
                    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\MantenimientosExport($exportData), 'Reporte_Operaciones_' . date('Y-m-d_His') . '.xlsx');
                } elseif ($tipo === 'solo_electronica') {
                    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ElectronicasExport($exportData), 'Reporte_Operaciones_' . date('Y-m-d_His') . '.xlsx');
                } else {
                    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReportesFinancierosExport($exportData), 'Reporte_Operaciones_' . date('Y-m-d_His') . '.xlsx');
                }
            }

            if ($request->get('export') === 'pdf') {
                if ($tipo === 'solo_mantenimientos') {
                    return \Barryvdh\DomPDF\Facade\Pdf::loadView('mantenimientos.pdf', ['mantenimientos' => $exportData])
                        ->setPaper('a4', 'portrait')
                        ->download('Reporte_Operaciones_' . date('Y-m-d_His') . '.pdf');
                } elseif ($tipo === 'solo_electronica') {
                    return \Barryvdh\DomPDF\Facade\Pdf::loadView('electronicas.pdf', ['electronicas' => $exportData])
                        ->setPaper('a4', 'portrait')
                        ->download('Reporte_Operaciones_' . date('Y-m-d_His') . '.pdf');
                } else {
                    $movimientosMapped = $exportData->map(function($tx) use ($tipo) {
                        return [
                            'fecha'       => $tx->fecha ?? $tx->fecha_entrada,
                            'tipo'        => $tx->tipo_movimiento ?? str_replace('solo_', '', $tipo),
                            'descripcion' => $tx->concepto->nombre ?? $tx->persona ?? ($tx->facturable->nombre ?? 'N/A'),
                            'monto'       => $tx->monto ?? $tx->total_documento ?? 0,
                            'estado'      => $tx->estado ?? '—',
                            'anulado'     => $tx->anulado ?? false,
                            'icono'       => '📋',
                            'color'       => 'gray'
                        ];
                    });
                    return \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes_financieros.pdf_diario', [
                        'movimientos' => $movimientosMapped,
                        'resumen' => [
                            'total_ingresos'       => $tipo === 'solo_ingresos' ? $movimientosMapped->sum('monto') : 0,
                            'total_egresos'        => $tipo === 'solo_egresos'  ? $movimientosMapped->sum('monto') : 0,
                            'total_mantenimientos' => 0,
                            'total_anulados'       => $movimientosMapped->where('anulado', true)->count(),
                        ],
                        'fecha' => "Del {$desde->format('d/m/Y')} al {$hasta->format('d/m/Y')} ({$tipoLabels[$tipo]})"
                    ])->setPaper('a4', 'portrait')
                      ->download('Reporte_Operaciones_' . date('Y-m-d_His') . '.pdf');
                }
            }
        }

        return view('reportes_financieros.operaciones', compact('registros', 'tipo', 'tipoLabels', 'desde', 'hasta'));
    }
}
