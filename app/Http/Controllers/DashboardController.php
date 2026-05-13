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

        // Filtros (persistidos)
        $filters = [
            'fecha_from' => $request->get('fecha_from'),
            'fecha_to' => $request->get('fecha_to'),
            'cliente_id' => $request->get('cliente_id'),
            'tecnico_id' => $request->get('tecnico_id'),
            'tipo' => $request->get('tipo'),
            'reparacion' => $request->get('reparacion'),
            'min_cost' => $request->get('min_cost'),
            'max_cost' => $request->get('max_cost'),
        ];

        // Construcción de mantenimientos filtrados para la vista embebida
        $mantenimientosQuery = Mantenimiento::with(['equipo','tecnico','user'])->newQuery();

        if ($filters['fecha_from']) $mantenimientosQuery->whereDate('fecha_entrada', '>=', $filters['fecha_from']);
        if ($filters['fecha_to']) $mantenimientosQuery->whereDate('fecha_entrada', '<=', $filters['fecha_to']);
        if ($filters['cliente_id']) {
            $mantenimientosQuery->whereHas('equipo', function($q) use ($filters) {
                $q->where('cliente_id', $filters['cliente_id']);
            });
        }
        if ($filters['tecnico_id']) $mantenimientosQuery->where('tecnico_id', $filters['tecnico_id']);
        if ($filters['tipo']) $mantenimientosQuery->where('tipo', $filters['tipo']);
        if ($filters['reparacion']) $mantenimientosQuery->where('reparacion', $filters['reparacion']);
        if ($filters['min_cost']) $mantenimientosQuery->where('costo', '>=', $filters['min_cost']);
        if ($filters['max_cost']) $mantenimientosQuery->where('costo', '<=', $filters['max_cost']);

        $mantenimientos = $mantenimientosQuery->orderBy('id','desc')->get();

        return view('dashboard', compact(
            'totalEquipos',
            'totalMantenimientos',
            'recentMant',
            'stats',
            'chartData',
            'clientes',
            'tecnicos',
            'mantenimientos',
            'filters',
            'totalCostoFormateado',
            'totalCostoDiaFormateado'
        ));
    }

}
