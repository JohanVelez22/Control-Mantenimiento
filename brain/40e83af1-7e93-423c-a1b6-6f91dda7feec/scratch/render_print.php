<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
}

$stock = \App\Models\Stock::first();
$proveedor = $stock ? $stock->proveedor()->first() : null;

$html = view('stocks.print', compact('stock', 'proveedor'))->render();
file_put_contents('stock_print_utf8.html', $html);
echo "Rendered successfully!\n";
