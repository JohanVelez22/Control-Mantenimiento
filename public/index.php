<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Mitigación en profundidad para CVE-2024-4577 (PHP-CGI Argument Injection en Windows)
if (isset($_SERVER['QUERY_STRING']) && (
    str_contains($_SERVER['QUERY_STRING'], '%ad') ||
    str_contains($_SERVER['QUERY_STRING'], '%AD') ||
    preg_match('/(?:^|[&? ])-(?:d|s|u|w)/i', (string) $_SERVER['QUERY_STRING'])
)) {
    http_response_code(400);
    exit('Bad Request');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registrar el autoloader de Composer...
require __DIR__.'/../vendor/autoload.php';

// Iniciar Laravel y manejar la solicitud...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
