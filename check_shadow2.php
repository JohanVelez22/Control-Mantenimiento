<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stocks = App\Models\Stock::whereNotNull('proveedor_id')->take(3)->get();
foreach ($stocks as $s) {
    echo "Stock ID: {$s->id}\n";
    echo "proveedor_id: {$s->proveedor_id}\n";
    echo "proveedor attr: {$s->proveedor}\n";
    $rel = $s->proveedor()->first();
    echo "proveedor()->first()->nombre_razon_social: " . ($rel ? $rel->nombre_razon_social : 'null') . "\n";
    echo "proveedor->nombre_razon_social: " . (is_object($s->proveedor) ? $s->proveedor->nombre_razon_social : gettype($s->proveedor)) . "\n";
    echo "---\n";
}
