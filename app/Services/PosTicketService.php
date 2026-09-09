<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

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

    /**
     * Genera y transmite el PDF de la tirilla POS con el tamaño vertical exacto
     * donde termina la información.
     *
     * @param string $view Nombre de la vista Blade
     * @param array $data Datos del contexto
     * @param string $filename Nombre del archivo PDF resultante
     * @param float $fallbackHeight Altura de respaldo si la medición no está disponible
     * @return \Illuminate\Http\Response
     */
    public static function streamTicket(string $view, array $data, string $filename, float $fallbackHeight = 700.0)
    {
        $maxY = 0;

        try {
            // Paso 1: Renderizado de medición sobre un lienzo infinito continuo (3500pt)
            // de modo que ningún elemento salte de página y podamos registrar la coordenada Y final.
            $measurer = Pdf::loadView($view, $data);
            $measurer->setPaper([0, 0, self::TICKET_WIDTH, 3500], 'portrait');
            $measurer->setCallbacks([
                [
                    'event' => 'end_frame',
                    'f' => function ($frame) use (&$maxY) {
                        $nodeName = strtolower($frame->get_node()->nodeName ?? '');
                        // Omitir contenedores raíz que ocupan todo el lienzo virtual de 3500pt
                        if (in_array($nodeName, ['html', 'body', '#document'])) {
                            return;
                        }
                        $y = (float)$frame->get_position('y') + (float)$frame->get_margin_height();
                        if ($y > $maxY) {
                            $maxY = $y;
                        }
                    }
                ]
            ]);
            $measurer->render();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error midiendo altura de ticket POS: ' . $e->getMessage());
            $maxY = 0;
        }

        // Si se calculó la coordenada final exacta, se añade un margen mínimo de 4pt (~1.4mm)
        // para un corte al ras inmediatamente después del pie de página.
        $finalHeight = ($maxY > 150) ? (ceil($maxY) + 4) : $fallbackHeight;

        // Paso 2: Generar el documento final con la altura exacta de la información
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper([0, 0, self::TICKET_WIDTH, $finalHeight], 'portrait');

        return $pdf->stream($filename);
    }
}
