<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     *
     * Uso: ->middleware('role:admin')
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!$request->user() || $request->user()->role !== $role) {
            // Opciones: redirigir o 403
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
        return $next($request);
    }
}
