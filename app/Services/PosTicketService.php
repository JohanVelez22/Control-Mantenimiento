<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la generación de tickets térmicos POS (80mm) con altura
 * calculada exactamente en función del contenido real renderizado (Corte Dinámico).
 */
class PosTicketService
{
    /**
     * Ancho estándar para papel térmico de 80mm expresado en puntos tipográficos (72 dpi).
     * 80mm ≈ 3.15 pulgadas = 226.77 pt.
     */
    const TICKET_WIDTH = 226.77;

    /** Alto máximo de una página PDF (200 pulgadas). */
    const ALTO_MAXIMO = 14400;

    /**
     * Genera y transmite el PDF de la tirilla POS con el tamaño vertical exacto
     * donde termina la información.
     *
     * @param  string  $view  Nombre de la vista Blade
     * @param  array  $data  Datos del contexto
     * @param  string  $filename  Nombre del archivo PDF resultante
     * @param  float  $fallbackHeight  Altura de respaldo si la medición no está disponible
     * @return Response
     */
    public static function streamTicket(string $view, array $data, string $filename, float $fallbackHeight = 700.0)
    {
        $maxY = 0;

        try {
            // Paso 1: Renderizado de medición sobre un lienzo continuo del alto máximo de un PDF
            // (14400 pt) para que ningún elemento salte de página y se registre la Y final.
            // X-14: con 3500 pt un ticket largo pasaba a una segunda página y la altura medida
            // se reiniciaba, dejando metros de papel térmico en blanco.
            $measurer = Pdf::loadView($view, $data);
            $measurer->setPaper([0, 0, self::TICKET_WIDTH, self::ALTO_MAXIMO], 'portrait');
            $measurer->setCallbacks([
                [
                    'event' => 'end_frame',
                    'f' => function ($frame) use (&$maxY) {
                        $nodeName = strtolower($frame->get_node()->nodeName ?? '');
                        // Omitir contenedores raíz que ocupan todo el lienzo virtual
                        if (in_array($nodeName, ['html', 'body', '#document'])) {
                            return;
                        }
                        $y = (float) $frame->get_position('y') + (float) $frame->get_margin_height();
                        if ($y > $maxY) {
                            $maxY = $y;
                        }
                    },
                ],
            ]);
            $measurer->render();
        } catch (\Throwable $e) {
            Log::warning('Error midiendo altura de ticket POS: '.$e->getMessage());
            $maxY = 0;
        }

        // Si se calculó la coordenada final exacta, se añade un margen mínimo de 4pt (~1.4mm)
        // para un corte al ras inmediatamente después del pie de página.
        $finalHeight = ($maxY > 150) ? min(self::ALTO_MAXIMO, ceil($maxY) + 4) : $fallbackHeight;

        // Paso 2: Generar el documento final con la altura exacta de la información
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper([0, 0, self::TICKET_WIDTH, $finalHeight], 'portrait');

        return $pdf->stream($filename);
    }
}
