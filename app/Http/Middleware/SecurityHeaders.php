<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Añade headers de seguridad HTTP a todas las respuestas.
 *
 * Protege contra:
 * - Clickjacking (X-Frame-Options)
 * - MIME-type sniffing (X-Content-Type-Options)
 * - Referrer leaks (Referrer-Policy)
 * - Acceso a APIs del navegador (Permissions-Policy)
 *
 * S-13: se aplican también a descargas (BinaryFileResponse / StreamedResponse), que antes
 * salían sin ninguna; X-XSS-Protection va en 0 (el filtro antiguo de los navegadores está
 * obsoleto y podía usarse para filtrar datos); HSTS solo por HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
