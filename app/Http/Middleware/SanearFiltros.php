<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B-05 / S-16: los filtros de los listados (búsqueda, estado, fechas…) son textos.
 * Un `?search[]=x` o `?fecha=xyz` armado a mano producía errores 500. Aquí se descartan
 * los arreglos en esos parámetros y las fechas que no se pueden interpretar, para que el
 * listado se muestre con su filtro por defecto.
 */
class SanearFiltros
{
    /** Parámetros de consulta que siempre son un solo valor. */
    private const ESCALARES = [
        'search', 'estado', 'tipo', 'tipo_movimiento', 'tipo_pago', 'categoria', 'subcategoria',
        'export', 'page', 'per_page', 'sort', 'direction', 'accion', 'modelo', 'usuario',
        'fecha', 'desde', 'hasta', 'fecha_desde', 'fecha_hasta', 'fecha_inicio', 'fecha_fin',
        'mes', 'anio', 'tecnico_id', 'cliente_id', 'equipo_id', 'user_id', 'concepto_id', 'departamento',
    ];

    /** Parámetros de consulta que deben ser una fecha. */
    private const FECHAS = ['fecha', 'desde', 'hasta', 'fecha_desde', 'fecha_hasta', 'fecha_inicio', 'fecha_fin'];

    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query->all();
        $limpio = $query;

        foreach (self::ESCALARES as $clave) {
            if (! array_key_exists($clave, $limpio)) {
                continue;
            }
            $valor = $limpio[$clave];
            if (is_array($valor)) {
                unset($limpio[$clave]);

                continue;
            }
            if (is_string($valor) && mb_strlen($valor) > 200) {
                $limpio[$clave] = mb_substr($valor, 0, 200);
            }
        }

        foreach (self::FECHAS as $clave) {
            if (isset($limpio[$clave]) && $limpio[$clave] !== '' && ! self::esFecha((string) $limpio[$clave])) {
                unset($limpio[$clave]);
            }
        }

        if ($limpio !== $query) {
            $request->query->replace($limpio);
            // `$request->input()` combina query y cuerpo: en GET se reconstruye desde la query.
            if ($request->isMethod('GET')) {
                $request->replace($limpio);
            }
        }

        return $next($request);
    }

    private static function esFecha(string $valor): bool
    {
        if (! preg_match('/^\d{4}-\d{2}(-\d{2})?([ T]\d{2}:\d{2}(:\d{2})?)?$/', $valor)) {
            return false;
        }
        $partes = array_map('intval', explode('-', substr($valor, 0, 10)));

        return checkdate($partes[1], $partes[2] ?? 1, $partes[0]);
    }
}
