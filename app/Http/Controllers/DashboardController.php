<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Tecnico;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Carga la vista principal del Dashboard con estadísticas y gráficos.
     */
    public function index(Request $request)
    {
        // Métricas rápidas: Conteo total de registros
        $totalEquipos = Equipo::count();
        $totalMantenimientos = Mantenimiento::where('anulado', false)->count();

        // Caja: ingresos, egresos y saldo actual del histórico
        $cajaIngresos = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $cajaEgresos = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)->where('tipo_movimiento', 'egreso')->sum('monto');
        $cajaSaldoActual = $cajaIngresos - $cajaEgresos;

        // Caja: saldo neto del día (Hoy)
        $ingresosHoy = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)
            ->where('tipo_movimiento', 'ingreso')
            ->whereDate('fecha', Carbon::today())
            ->sum('monto');
            
        $egresosHoy = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)
            ->where('tipo_movimiento', 'egreso')
            ->whereDate('fecha', Carbon::today())
            ->sum('monto');
            
        $cajaSaldoDia = $ingresosHoy - $egresosHoy;

        // Formateo para la vista
        $totalCostoFormateado = number_format($cajaSaldoActual, 0, ',', '.');
        $totalCostoDiaFormateado = number_format($cajaSaldoDia, 0, ',', '.');

        // Mantenimientos recientes (incluye abonos para calcular saldos pendientes)
        $recentMant = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user', 'abonos'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Estados Mantenimientos
        $stats = [
            'pendientes' => Mantenimiento::where('anulado', false)->where('estado', 'pendiente')->count(),
            'en_proceso' => Mantenimiento::where('anulado', false)->where('estado', 'en_proceso')->count(),
            'reparados'  => Mantenimiento::where('anulado', false)->where('estado', 'reparado')->count(),
            'terminados' => Mantenimiento::where('anulado', false)->whereIn('estado', ['terminado', 'entregado'])->count(),
            'stock_bajo' => \App\Models\Stock::where('cantidad', '<=', 5)->count(),
            'electronica_pendientes' => \App\Models\Electronica::where('anulado', false)->where('estado', 'pendiente')->count(),
        ];

        // --- Gráficos de los últimos 7 días: 3 queries agrupadas en lugar de 28 ---
        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $endDate   = Carbon::today()->endOfDay();

        // 1 query: equipos registrados por día
        $equiposPorDia = Equipo::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        // 1 query: mantenimientos por día de entrada
        $mantPorDia = Mantenimiento::where('anulado', false)->whereBetween('fecha_entrada', [$startDate, $endDate])
            ->selectRaw('DATE(fecha_entrada) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        // 1 query: ingresos por caja (ingresos - egresos) de los últimos 7 días
        $cajaMovs = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)
            ->whereBetween('fecha', [$startDate, $endDate])
            ->selectRaw("DATE(fecha) as fecha, tipo_movimiento, SUM(monto) as total")
            ->groupBy('fecha', 'tipo_movimiento')
            ->get();

        // Construir arrays para Chart.js en PHP (sin queries adicionales)
        $labels = [];
        $dataEquipos = [];
        $dataMantenimientos = [];
        $dataIngresos = [];
        $dataIngresosAcumulados = [];

        // Para el acumulado debemos partir del saldo histórico ANTES de los 7 días
        $saldoAnterior = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', false)
            ->where('fecha', '<', $startDate)
            ->selectRaw("SUM(CASE WHEN tipo_movimiento='ingreso' THEN monto ELSE -monto END) as saldo")
            ->value('saldo') ?? 0;
            
        $acumulado = $saldoAnterior;

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays(6 - $i);
            $key  = $date->format('Y-m-d');
            $labels[]             = $date->format('d/m');
            $dataEquipos[]        = (int)   ($equiposPorDia[$key]  ?? 0);
            $dataMantenimientos[] = (int)   ($mantPorDia[$key]     ?? 0);
            
            $ingresoDia = $cajaMovs->where('fecha', $key)->where('tipo_movimiento', 'ingreso')->sum('total');
            $egresoDia = $cajaMovs->where('fecha', $key)->where('tipo_movimiento', 'egreso')->sum('total');
            $saldoDia = $ingresoDia - $egresoDia;

            $dataIngresos[]       = $saldoDia;
            $acumulado           += $saldoDia;
            $dataIngresosAcumulados[] = $acumulado;
        }

        // Estadísticas de Electrónica para el slide del dashboard
        $electronicaPendientes  = Electronica::where('anulado', false)->where('estado', 'pendiente')->count();
        $electronicaTerminados  = Electronica::where('anulado', false)->where('estado', 'terminado')->count();
        $electronicaCorrectivos = Electronica::where('anulado', false)->where('tipo', 'correctivo')->count();
        $electronicaPreventivos = Electronica::where('anulado', false)->where('tipo', 'preventivo')->count();
        // 5 más recientes (cualquier estado) para la tabla del dashboard
        $recentElec = Electronica::with(['tecnico', 'equipo.cliente'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();
        // Pendientes más antiguos para el slide del carrusel
        $electronicaRecientes = Electronica::with(['tecnico', 'equipo.cliente'])
            ->where('estado', 'pendiente')
            ->orderBy('fecha_entrada', 'asc')
            ->take(5)
            ->get();

        $chartData = [
            'labels'                  => $labels,
            'equipos'                 => $dataEquipos,
            'mantenimientos'          => $dataMantenimientos,
            'ingresos'                => $dataIngresos,
            'ingresosAcumulados'      => $dataIngresosAcumulados,
            // Datos para el slide 4: resumen electrónica
            'electronicaPendientes'   => $electronicaPendientes,
            'electronicaTerminados'   => $electronicaTerminados,
            'electronicaCorrectivos'  => $electronicaCorrectivos,
            'electronicaPreventivos'  => $electronicaPreventivos,
        ];

        // Pasar los recientes por separado para el blade
        $electronicaRecientes = $electronicaRecientes;

        // Listas para selects (solo columnas necesarias)
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);

        return view('dashboard', compact(
            'totalEquipos',
            'totalMantenimientos',
            'recentMant',
            'recentElec',
            'stats',
            'chartData',
            'clientes',
            'tecnicos',
            'cajaSaldoActual',
            'cajaSaldoDia',
            'totalCostoFormateado',
            'totalCostoDiaFormateado',
            'electronicaRecientes'
        ));
    }

}
