<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Configuracion;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Programación de Respaldos Dinámica
try {
    if (Schema::hasTable('configuraciones')) {
        $config = Configuracion::first();
        if (! $config || ($config->backup_automatico ?? true)) {
            $frecuencia = $config?->backup_frecuencia ?? 'daily';
            $hora = $config?->backup_hora ?: '02:00';
            $diaSemana = $config?->backup_dia_semana ?? 'sunday';
            $diaMes = (int) ($config?->backup_dia_mes ?: 1);
            $tipo = $config?->backup_tipo_incluido ?? 'db';

            $cmd = 'app:backup-db';
            if ($tipo === 'files') {
                $cmd .= ' --files';
            } elseif ($tipo === 'all') {
                $cmd .= ' --all';
            }

            $scheduler = Schedule::command($cmd);

            if ($frecuencia === 'weekly') {
                $diaSemanaLower = strtolower($diaSemana);
                if ($diaSemanaLower === 'mjs' || $diaSemanaLower === 'martes_jueves_sabado') {
                    // Martes (2), Jueves (4), Sábado (6)
                    $scheduler->dailyAt($hora)->days([2, 4, 6]);
                } elseif ($diaSemanaLower === 'lmv' || $diaSemanaLower === 'lunes_miercoles_viernes') {
                    // Lunes (1), Miércoles (3), Viernes (5)
                    $scheduler->dailyAt($hora)->days([1, 3, 5]);
                } else {
                    $dayMap = [
                        'sunday' => 0, 'domingo' => 0, '0' => 0,
                        'monday' => 1, 'lunes' => 1, '1' => 1,
                        'tuesday' => 2, 'martes' => 2, '2' => 2,
                        'wednesday' => 3, 'miercoles' => 3, 'miércoles' => 3, '3' => 3,
                        'thursday' => 4, 'jueves' => 4, '4' => 4,
                        'friday' => 5, 'viernes' => 5, '5' => 5,
                        'saturday' => 6, 'sabado' => 6, 'sábado' => 6, '6' => 6,
                    ];
                    $dayNumber = $dayMap[$diaSemanaLower] ?? 1;
                    $scheduler->weeklyOn($dayNumber, $hora);
                }
            } elseif ($frecuencia === 'monthly') {
                $diaMes = max(1, min(28, $diaMes));
                $scheduler->monthlyOn($diaMes, $hora);
            } else {
                $scheduler->dailyAt($hora);
            }

            $scheduler->withoutOverlapping();
        }
    } else {
        Schedule::command('app:backup-db')->dailyAt('02:00')->withoutOverlapping();
    }
} catch (\Throwable $e) {
    Schedule::command('app:backup-db')->dailyAt('02:00')->withoutOverlapping();
}

