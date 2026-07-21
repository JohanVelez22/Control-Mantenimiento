<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\Tecnico;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class DashboardController extends Controller
{
    /**
     * Carga la vista principal del Dashboard con estadísticas y gráficos.
     */
    public function index(Request $request)
    {
        // Invitados tienen su propio panel dedicado
        if (auth()->user()?->role === 'invitado') {
            return Redirect::route('guest.dashboard');
        }
        // ─── Métricas consolidadas (Eloquent puro) ───────────
        $totalEquipos = \App\Models\Equipo::count();
        $totalMantenimientos = \App\Models\Mantenimiento::where('anulado', 0)->count();
        $counts = (object) [
            'mant_pendientes' => \App\Models\Mantenimiento::where('anulado', 0)->where('estado', 'pendiente')->count(),
            'mant_terminados' => \App\Models\Mantenimiento::where('anulado', 0)->where('estado', 'terminado')->count(),
            'stock_bajo' => \App\Models\Stock::where('cantidad', '<=', 5)->count(),
            'elec_pendientes' => \App\Models\Electronica::where('anulado', 0)->where('estado', 'pendiente')->count(),
        ];

        $today = Carbon::today()->toDateString();
        
        $baseCaja = \App\Models\MovimientoCaja::where('estado', 'activo')->where('anulado', 0);
        $cajaIngresos = (clone $baseCaja)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $cajaEgresos = (clone $baseCaja)->where('tipo_movimiento', 'egreso')->sum('monto');
        $cajaIngresosHoy = (clone $baseCaja)->where('tipo_movimiento', 'ingreso')->whereDate('fecha', $today)->sum('monto');
        $cajaEgresosHoy = (clone $baseCaja)->where('tipo_movimiento', 'egreso')->whereDate('fecha', $today)->sum('monto');

        $cajaSaldoActual = $cajaIngresos - $cajaEgresos;
        $cajaSaldoDia = $cajaIngresosHoy - $cajaEgresosHoy;

        // Formateo para la vista
        $totalCostoFormateado = number_format($cajaSaldoActual, 0, ',', '.');
        $totalCostoDiaFormateado = number_format($cajaSaldoDia, 0, ',', '.');

        // Mantenimientos recientes (usando withSum para evitar N+1 en abonos)
        $recentMant = Mantenimiento::with(['equipo.cliente', 'tecnico', 'user'])
            ->withSum('abonos as total_abonado', 'monto')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Estados Mantenimientos (ya calculados arriba)
        $stats = [
            'pendientes' => $counts->mant_pendientes,
            'terminados' => $counts->mant_terminados,
            'stock_bajo' => $counts->stock_bajo,
            'electronica_pendientes' => $counts->elec_pendientes,
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

        // 1 query: costo de mantenimientos terminados por fecha_salida de los últimos 7 días
        $costoTerminadosPorDia = Mantenimiento::where('anulado', false)
            ->where('estado', 'terminado')
            ->whereBetween('fecha_salida', [$startDate, $endDate])
            ->selectRaw("DATE(fecha_salida) as fecha, SUM(costo) as total")
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        // Construir arrays para Chart.js en PHP (sin queries adicionales)
        $labels = [];
        $dataEquipos = [];
        $dataMantenimientos = [];
        $dataIngresos = [];
        $dataIngresosAcumulados = [];

        // Para el acumulado debemos partir del saldo histórico de mantenimientos terminados
        $costoAnterior = Mantenimiento::where('anulado', false)
            ->where('estado', 'terminado')
            ->where('fecha_salida', '<', $startDate)
            ->sum('costo');
            
        $acumulado = $costoAnterior;

        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->subDays(6 - $i);
            $key  = $date->format('Y-m-d');
            $labels[]             = $date->format('d/m');
            $dataEquipos[]        = (int)   ($equiposPorDia[$key]  ?? 0);
            $dataMantenimientos[] = (int)   ($mantPorDia[$key]     ?? 0);
            $costoDia = (float) ($costoTerminadosPorDia[$key] ?? 0);

            $dataIngresos[]       = $costoDia;
            $acumulado           += $costoDia;
            $dataIngresosAcumulados[] = $acumulado;
        }

        // Estadísticas de Electrónica consolidadas (1 query en lugar de 4)
        $elecStats = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                SUM(anulado = 0 AND estado = 'pendiente') as pendientes,
                SUM(anulado = 0 AND estado = 'terminado') as terminados,
                SUM(anulado = 0 AND tipo = 'correctivo') as correctivos,
                SUM(anulado = 0 AND tipo = 'preventivo') as preventivos
            FROM electronicas
        ");
        $electronicaPendientes  = (int) $elecStats->pendientes;
        $electronicaTerminados  = (int) $elecStats->terminados;
        $electronicaCorrectivos = (int) $elecStats->correctivos;
        $electronicaPreventivos = (int) $elecStats->preventivos;
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
        $clientes = Cliente::orderBy('nombres')->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
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
