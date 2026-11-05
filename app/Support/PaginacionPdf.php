<?php

namespace App\Support;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;

/**
 * Numeración "Página X de Y" para los PDF sin habilitar PHP embebido en dompdf.
 *
 * Antes cada plantilla usaba <script type="text/php">, lo que exige `enable_php`: con esa
 * opción, cualquier texto sin escapar en un PDF podía ejecutar código en el servidor.
 * Ahora la plantilla declara un marcador en el <head> y este callback dibuja el número en
 * cada página (dompdf elimina del DOM los nodos del <body> al renderizar; los <meta> no):
 *
 *   <meta name="paginacion" content='{"x":85,"y":24,"size":7.5,"color":[0.44,0.5,0.59]}'>
 *
 * x / y: distancia en puntos desde el borde derecho / inferior de la página.
 */
class PaginacionPdf
{
    public static function registrar(Dompdf $dompdf): Dompdf
    {
        $config = null;

        return $dompdf->setCallbacks([[
            'event' => 'end_document',
            'f' => function (int $pagina, int $total, Canvas $canvas, FontMetrics $fontMetrics) use ($dompdf, &$config) {
                $config ??= self::leerMarcador($dompdf);
                if ($config === false) {
                    return;
                }

                $font = $fontMetrics->getFont($config['font'] ?? 'Helvetica', 'normal');
                $canvas->text(
                    $canvas->get_width() - (float) ($config['x'] ?? 85),
                    $canvas->get_height() - (float) ($config['y'] ?? 24),
                    "Página {$pagina} de {$total}",
                    $font,
                    (float) ($config['size'] ?? 7.5),
                    $config['color'] ?? [0.44, 0.5, 0.59]
                );
            },
        ]]);
    }

    /** @return array<string, mixed>|false */
    private static function leerMarcador(Dompdf $dompdf): array|false
    {
        $dom = $dompdf->getDom();
        if (! $dom) {
            return false;
        }

        foreach ((new \DOMXPath($dom))->query('//meta[@name="paginacion"]') as $nodo) {
            $datos = json_decode((string) $nodo->getAttribute('content'), true);

            return is_array($datos) ? $datos : [];
        }

        return false;
    }
}
