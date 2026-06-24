<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/mantenimientos/reportes', 'GET', ['export' => 'pdf']);
$request->setUserResolver(function() { return App\Models\User::first(); });

$response = $kernel->handle($request);
echo json_encode($response->headers->all());
