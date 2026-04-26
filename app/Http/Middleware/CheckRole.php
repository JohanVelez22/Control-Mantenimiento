<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Verificamos si el usuario está desactivado
        if (Auth::check() && !Auth::user()->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta ha sido desactivada por el administrador.'
            ]);
        }

        // 2. Verificamos el rol
        if (!Auth::check() || Auth::user()->role !== $role) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
