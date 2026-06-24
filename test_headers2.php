<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/mantenimientos/reportes', 'GET', ['export' => 'pdf']);
$kernel->handle($request);
// It might still redirect because session middleware isn't fully mocked.

// Let's test the PDF download manually!
$user = App\Models\User::first();
Auth::login($user);
$controller = app()->make(App\Http\Controllers\MantenimientoController::class);
$response = $controller->reportes($request);
echo json_encode($response->headers->all());
