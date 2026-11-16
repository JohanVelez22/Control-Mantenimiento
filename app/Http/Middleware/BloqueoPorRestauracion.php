<?php

namespace App\Http\Middleware;

use App\Support\ModoRestauracion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** D-01: página de mantenimiento mientras se restaura un respaldo (ver ModoRestauracion). */
class BloqueoPorRestauracion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! ModoRestauracion::activo()) {
            return $next($request);
        }

        $mensaje = 'Se está restaurando una copia de seguridad. El sistema estará disponible en unos minutos.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $mensaje], 503, ['Retry-After' => 60]);
        }

        return response()->view('errors.restaurando', ['mensaje' => $mensaje], 503, ['Retry-After' => 60]);
    }
}
