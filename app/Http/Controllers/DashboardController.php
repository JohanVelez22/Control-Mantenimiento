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
        // ─── Métricas consolidadas (1 query en lugar de 7) ───────────
        $counts = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM equipos) as total_equipos,
                (SELECT COUNT(*) FROM mantenimientos WHERE anulado = 0) as total_mant,
                (SELECT COUNT(*) FROM mantenimientos WHERE anulado = 0 AND estado = 'pendiente') as mant_pendientes,
                (SELECT COUNT(*) FROM mantenimientos WHERE anulado = 0 AND estado = 'terminado') as mant_terminados,
                (SELECT COUNT(*) FROM stocks WHERE cantidad <= 5) as stock_bajo,
                (SELECT COUNT(*) FROM electronicas WHERE anulado = 0 AND estado = 'pendiente') as elec_pendientes
        ");

        $totalEquipos = $counts->total_equipos;
        $totalMantenimientos = $counts->total_mant;

        // Caja: consolidar ingresos/egresos en 1 sola query (histórico + hoy)
        $caja = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN tipo_movimiento='ingreso' THEN monto ELSE 0 END), 0) as ingresos,
                COALESCE(SUM(CASE WHEN tipo_movimiento='egreso'  THEN monto ELSE 0 END), 0) as egresos,
                COALESCE(SUM(CASE WHEN tipo_movimiento='ingreso' AND DATE(fecha) = CURDATE() THEN monto ELSE 0 END), 0) as ingresos_hoy,
                COALESCE(SUM(CASE WHEN tipo_movimiento='egreso'  AND DATE(fecha) = CURDATE() THEN monto ELSE 0 END), 0) as egresos_hoy
            FROM movimiento_cajas
            WHERE estado = 'activo' AND anulado = 0
        ");

        $cajaIngresos = $caja->ingresos;
        $cajaEgresos = $caja->egresos;
        $cajaSaldoActual = $cajaIngresos - $cajaEgresos;
        $cajaSaldoDia = $caja->ingresos_hoy - $caja->egresos_hoy;

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
