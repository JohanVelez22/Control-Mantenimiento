<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
}

$mantenimiento = \App\Models\Mantenimiento::first();
$html = view('mantenimientos.factura', compact('mantenimiento'))->render();
file_put_contents('mantenimiento_factura_utf8.html', $html);
echo "Rendered maintenance factura successfully!\n";
