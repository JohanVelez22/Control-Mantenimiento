<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MovimientoCaja;
use App\Models\Mantenimiento;
use App\Models\Stock;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->get('mes', Carbon::now()->month);
        $anio = $request->get('anio', Carbon::now()->year);

        // --- INFORME DETALLADO ---
        $queryDetallado = MovimientoCaja::with(['concepto', 'user'])
            ->where('estado', 'activo')
            ->where('anulado', false)
            ->orderBy('id', 'desc');

        if ($request->filled('mes')) {
            $queryDetallado->whereMonth('fecha', $mes)->whereYear('fecha', $anio);
        }

        if ($request->filled('tipo_movimiento')) {
            $queryDetallado->where('tipo_movimiento', $request->tipo_movimiento);
        }
        if ($request->filled('tipo_pago')) {
            $queryDetallado->where('tipo_pago', $request->tipo_pago);
        }

        $transacciones = $queryDetallado->paginate(10, ['*'], 'transacciones_page');

        // --- INFORME ACUMULADO (MES ACTUAL O FILTRADO) ---
        $acumuladoQuery = MovimientoCaja::where('estado', 'activo')
            ->where('anulado', false)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio);

        $ingresos_mes = (clone $acumuladoQuery)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos_mes = (clone $acumuladoQuery)->where('tipo_movimiento', 'egreso')->sum('monto');
        $saldo_mes = $ingresos_mes - $egresos_mes;

        // Facturación Total (Mantenimientos + Electrónica) - Lo que se ha cobrado o se debe cobrar
        $facturado_mantenimientos = Mantenimiento::where('anulado', false)
            ->whereMonth('fecha_entrada', $mes)
            ->whereYear('fecha_entrada', $anio)
            ->sum('costo');

        $facturado_electronica = \App\Models\Electronica::where('anulado', false)
            ->whereMonth('fecha_entrada', $mes)
            ->whereYear('fecha_entrada', $anio)
            ->sum('costo');

        $facturado_total = $facturado_mantenimientos + $facturado_electronica;

        // Inventario valorizado
        $costo_inventario = Stock::sum(\DB::raw('cantidad * precio_compra'));
        $utilidad_esperada = Stock::sum(\DB::raw('cantidad * (precio_venta - precio_compra)'));

        $acumulado = [
            'ingresos' => $ingresos_mes,
            'egresos' => $egresos_mes,
            'saldo' => $saldo_mes,
            'facturado_total' => $facturado_total,
            'utilidad_neta' => $saldo_mes, // La utilidad en caja es simplemente ingresos - egresos. Lo facturado es independiente.
            'inventario_costo' => $costo_inventario,
            'inventario_utilidad_esperada' => $utilidad_esperada,
        ];

        // --- INFORME POR OPERACIONES ---
        $operaciones = [
            'efectivo' => (clone $acumuladoQuery)->where('tipo_pago', 'efectivo')->sum('monto'),
            'consignacion' => (clone $acumuladoQuery)->where('tipo_pago', 'consignacion')->sum('monto'),
            'ingresos_efectivo' => (clone $acumuladoQuery)->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'efectivo')->sum('monto'),
            'ingresos_consignacion' => (clone $acumuladoQuery)->where('tipo_movimiento', 'ingreso')->where('tipo_pago', 'consignacion')->sum('monto'),
            'egresos_efectivo' => (clone $acumuladoQuery)->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'efectivo')->sum('monto'),
            'egresos_consignacion' => (clone $acumuladoQuery)->where('tipo_movimiento', 'egreso')->where('tipo_pago', 'consignacion')->sum('monto'),
        ];

        // Lógica de exportación según el botón presionado
        if ($request->get('export') == 'excel') {
            $transaccionesParaExportar = $queryDetallado->get();
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\ReportesFinancierosExport($transaccionesParaExportar), 'reporte_financiero.xlsx');
        }
        if ($request->get('export') == 'pdf') {
            $transaccionesParaExportar = $queryDetallado->get();
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.pdf', compact('transaccionesParaExportar', 'acumulado', 'operaciones', 'mes', 'anio'))
                ->setPaper('letter', 'portrait')
                ->download('reporte_financiero.pdf');
        }

        return view('reportes.index', compact('transacciones', 'acumulado', 'operaciones', 'mes', 'anio'));
    }
}
