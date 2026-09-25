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
