<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Tecnico;

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
            ->whereDate('fecha_salida', \Carbon\Carbon::today())
            ->sum('costo');
        $totalCostoDiaFormateado = number_format($totalCostoDia, 2, '.', ',');

        // Mantenimientos recientes
        $recentMant = Mantenimiento::with(['equipo','tecnico','user'])
            ->orderBy('id','desc')
            ->take(5)
            ->get();

        // Estados
        $stats = [
            'pendientes' => Mantenimiento::where('estado','pendiente')->count(),
            'terminados' => Mantenimiento::where('estado','terminado')->count(),
        ];

        // Generar datos para gráficos de los últimos 7 días
        $labels = [];
        $dataEquipos = [];
        $dataMantenimientos = [];
        
        $dataIngresos = [];
        $dataIngresosAcumulados = [];
        for ($i = 0; $i < 7; $i++) {
            $date = \Carbon\Carbon::today()->subDays(6 - $i);
            $labels[] = $date->format('d/m');
            $dataEquipos[] = Equipo::whereDate('created_at', $date)->count();
            $dataMantenimientos[] = Mantenimiento::whereDate('fecha_entrada', $date)->count();
            $dataIngresos[] = (float) Mantenimiento::where('estado', 'terminado')
                ->whereNotNull('fecha_salida')
                ->whereDate('fecha_salida', $date)
                ->sum('costo');
            $dataIngresosAcumulados[] = (float) Mantenimiento::where('estado', 'terminado')
                ->whereNotNull('fecha_salida')
                ->whereDate('fecha_salida', '<=', $date)
                ->sum('costo');
        }

        // Top Técnicos (Mantenimientos)
        $topTecnicos = Tecnico::withCount([
            'mantenimientos as terminados_count' => function ($query) {
                $query->where('estado', 'terminado');
            },
            'mantenimientos as recibidos_count'
        ])->orderBy('terminados_count', 'desc')->take(5)->get();

        $chartData = [
            'labels' => $labels,
            'equipos' => $dataEquipos,
            'mantenimientos' => $dataMantenimientos,
            'ingresos' => $dataIngresos,
            'ingresosAcumulados' => $dataIngresosAcumulados,
            'topTecnicosLabels' => $topTecnicos->pluck('nombre')->toArray(),
            'topTecnicosData' => $topTecnicos->pluck('terminados_count')->toArray(),
            'topTecnicosRecibidosData' => $topTecnicos->pluck('recibidos_count')->toArray(),
        ];

        // Listas para selects
        $clientes = Cliente::orderBy('nombre')->get();
        $tecnicos = Tecnico::orderBy('nombre')->get();

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
