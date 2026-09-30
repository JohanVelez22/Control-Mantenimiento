<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confiar solo en proxies de la maquina local (ngrok).
        // Necesario para que Laravel use X-Forwarded-Host y X-Forwarded-Proto,
        // de modo que las URLs y redirecciones se generen con el dominio
        // publico del tunel y no con el dominio local.
        $middleware->trustProxies(at: ['127.0.0.1', '::1'], headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Headers de seguridad en TODAS las respuestas HTTP
        $middleware->append(SecurityHeaders::class);

        // Registramos aliases para middlewares personalizados
        $middleware->alias([
            'role' => CheckRole::class,
            'prevent-back-history' => PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
