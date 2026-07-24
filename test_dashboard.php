<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/dashboard', 'GET')
);
$kernel->terminate($request, $response);
echo "Status: " . $response->status() . "\n";
if ($response->status() === 500 && $response->exception) {
    echo $response->exception->getMessage() . "\n";
    echo $response->exception->getFile() . ":" . $response->exception->getLine() . "\n";
}
