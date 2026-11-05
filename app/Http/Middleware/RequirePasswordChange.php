<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si la cuenta tiene una contraseña predeterminada o asignada por un administrador,
 * no se puede usar el sistema hasta definir una propia.
 */
class RequirePasswordChange
{
    /** Rutas disponibles mientras el cambio está pendiente. */
    private const PERMITIDAS = ['password.cambiar', 'password.actualizar', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->must_change_password && ! $request->routeIs(...self::PERMITIDAS)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe cambiar su contraseña antes de continuar.',
                ], 403);
            }

            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
