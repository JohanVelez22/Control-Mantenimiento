<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /** D-23: filas máximas por exportación (el PDF se arma en memoria con dompdf). */
    protected const LIMITE_EXPORTACION = ['excel' => 20000, 'pdf' => 3000];

    /**
     * Si la exportación supera el tope, devuelve una redirección con el aviso para acotar el
     * filtro (antes podía agotar la memoria o el tiempo del servidor y dar un error 500).
     */
    protected function limiteExportacion(int $filas, ?string $formato): ?RedirectResponse
    {
        $tope = self::LIMITE_EXPORTACION[$formato] ?? null;
        if ($tope === null || $filas <= $tope) {
            return null;
        }

        return back()->with('error', 'La exportación tiene '.number_format($filas, 0, ',', '.').' registros y el máximo en '
            .strtoupper($formato).' es '.number_format($tope, 0, ',', '.').'. Acote el rango de fechas o los filtros.');
    }
}
