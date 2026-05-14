<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
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
        $totalMantenimientos = Mantenimiento::count();

        // Cálculo del costo total acumulado sumando la columna 'costo' solo de órdenes terminadas con fecha de salida
        $totalCosto = Mantenimiento::where('estado', 'terminado')
            ->whereNotNull('fecha_salida')
            ->sum('costo');
        $totalCostoFormateado = number_format($totalCosto, 2, '.', ',');

        // Cálculo del costo del día (Hoy)
        $totalCostoDia = Mantenimiento::where('estado', 'terminado')
            ->whereNotNull('fecha_salida')
            ->whereDate('fecha_salida', Carbon::today())
            ->sum('costo');
        $totalCostoDiaFormateado = number_format($totalCostoDia, 2, '.', ',');

        // Mantenimientos recientes
        $recentMant = Mantenimiento::with(['equipo', 'tecnico', 'user'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Estados
        $stats = [
            'pendientes' => Mantenimiento::where('estado', 'pendiente')->count(),
            'terminados' => Mantenimiento::where('estado', 'terminado')->count(),
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
        $mantPorDia = Mantenimiento::whereBetween('fecha_entrada', [$startDate, $endDate])
            ->selectRaw('DATE(fecha_entrada) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        // 1 query: ingresos por día de salida (solo terminados)
        $ingresosPorDia = Mantenimiento::where('estado', 'terminado')
            ->whereNotNull('fecha_salida')
            ->whereBetween('fecha_salida', [$startDate, $endDate])
            ->selectRaw('DATE(fecha_salida) as fecha, SUM(costo) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        // Construir arrays para Chart.js en PHP (sin queries adicionales)
        $labels = [];
        $dataEquipos = [];
        $dataMantenimientos = [];
        $dataIngresos = [];
        $dataIngresosAcumulados = [];
        $acumulado = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays(6 - $i);
            $key  = $date->format('Y-m-d');
            $labels[]             = $date->format('d/m');
            $dataEquipos[]        = (int)   ($equiposPorDia[$key]  ?? 0);
            $dataMantenimientos[] = (int)   ($mantPorDia[$key]     ?? 0);
            $ingresoDia           = (float) ($ingresosPorDia[$key] ?? 0);
            $dataIngresos[]       = $ingresoDia;
            $acumulado           += $ingresoDia;
            $dataIngresosAcumulados[] = $acumulado;
        }

        // Top Técnicos (2 queries con withCount)
        $topTecnicos = Tecnico::withCount([
            'mantenimientos as terminados_count' => function ($query) {
                $query->where('estado', 'terminado');
            },
            'mantenimientos as recibidos_count'
        ])->orderBy('terminados_count', 'desc')->take(5)->get();

        $chartData = [
            'labels'                  => $labels,
            'equipos'                 => $dataEquipos,
            'mantenimientos'          => $dataMantenimientos,
            'ingresos'                => $dataIngresos,
            'ingresosAcumulados'      => $dataIngresosAcumulados,
            'topTecnicosLabels'       => $topTecnicos->pluck('nombre')->toArray(),
            'topTecnicosData'         => $topTecnicos->pluck('terminados_count')->toArray(),
            'topTecnicosRecibidosData'=> $topTecnicos->pluck('recibidos_count')->toArray(),
        ];

        // Listas para selects (solo columnas necesarias)
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre']);
        $tecnicos = Tecnico::orderBy('nombre')->get(['id', 'nombre']);

        return view('dashboard', compact(
            'totalEquipos',
            'totalMantenimientos',
            'recentMant',
            'stats',
            'chartData',
            'clientes',
            'tecnicos',
            'totalCostoFormateado',
            'totalCostoDiaFormateado'
        ));
    }

}
