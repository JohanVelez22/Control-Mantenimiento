<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotencia de formularios (M-07): el mismo envío no se procesa dos veces.
 *
 * El navegador agrega a cada formulario de escritura un token único `_idem` (layouts/app).
 * Si llega un segundo envío con el mismo token (doble clic, reintento de red, botón Atrás
 * y reenviar), se rechaza sin ejecutar nada: así una venta, un abono o un repuesto no se
 * registran dos veces aunque el bloqueo del navegador falle.
 *
 * Si el primer envío termina con error (validación, regla de negocio, excepción), el token
 * se libera para que el usuario pueda corregir y reenviar el mismo formulario.
 * Las peticiones sin token (API interna, pruebas, formularios antiguos) pasan sin cambios.
 */
class IdempotenciaFormulario
{
    private const TTL_SEGUNDOS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('_idem');
        if ($request->isMethodSafe() || ! is_string($token) || ! preg_match('/^[A-Za-z0-9-]{16,64}$/', $token)) {
            return $next($request);
        }

        $ambito = $request->user()?->getAuthIdentifier() ?? ($request->hasSession() ? $request->session()->getId() : $request->ip());
        $clave = "idem:{$ambito}:{$token}";

        if (! Cache::add($clave, 'en_proceso', self::TTL_SEGUNDOS)) {
            $mensaje = 'Esta operación ya fue enviada y procesada; se ignoró el envío repetido para no duplicarla.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'duplicado' => true, 'message' => $mensaje], 409)
                : redirect()->back()->with('warning', $mensaje);
        }

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Cache::forget($clave);
            throw $e;
        }

        if ($this->fallo($request, $response)) {
            Cache::forget($clave);
        } else {
            Cache::put($clave, 'procesado', self::TTL_SEGUNDOS);
        }

        return $response;
    }

    /** El envío no produjo cambios: error HTTP o redirección con errores de validación / negocio. */
    private function fallo(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        if (! $request->hasSession()) {
            return false;
        }

        $nuevos = (array) $request->session()->get('_flash.new', []);

        return in_array('errors', $nuevos, true) || in_array('error', $nuevos, true);
    }
}
