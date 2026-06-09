<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\MovimientoCaja;
use App\Models\Mantenimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

#[Signature('stats:reset-monthly')]
#[Description('Respalda los costos y totales del mes que termina y reinicia el flujo contable aislando el historial.')]
class ResetMonthlyStats extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastMonth = Carbon::now()->subMonth();
        $mes = $lastMonth->month;
        $anio = $lastMonth->year;

        $this->info("Generando respaldo histórico para {$mes}/{$anio}...");

        $ingresos = MovimientoCaja::where('estado', 'activo')->whereMonth('fecha', $mes)->whereYear('fecha', $anio)->where('tipo_movimiento', 'ingreso')->sum('monto');
        $egresos = MovimientoCaja::where('estado', 'activo')->whereMonth('fecha', $mes)->whereYear('fecha', $anio)->where('tipo_movimiento', 'egreso')->sum('monto');
        $costos = Mantenimiento::where('estado', '!=', 'anulado')->whereMonth('fecha_entrada', $mes)->whereYear('fecha_entrada', $anio)->sum('costo');

        $data = [
            'fecha_respaldo' => now()->toDateTimeString(),
            'periodo' => "{$anio}-{$mes}",
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'utilidad_bruta' => $ingresos - $egresos,
            'costos_mantenimiento' => $costos,
        ];

        // Respaldar histórico en disco local (JSON)
        $filename = "historial_mensual_{$anio}_{$mes}.json";
        Storage::disk('local')->put("respaldos/{$filename}", json_encode($data, JSON_PRETTY_PRINT));

        // NOTA: No borramos los registros físicos (borrado lógico o físico), 
        // simplemente archivamos los totales ya que las vistas Dashboard/Reportes 
        // usan automáticamente whereMonth(now()), con lo que visualmente inician en 0.
        
        $this->info("¡Reseteo mensual completado! Histórico guardado en storage/app/respaldos/{$filename}");
    }
}
