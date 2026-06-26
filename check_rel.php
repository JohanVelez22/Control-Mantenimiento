<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stocks = App\Models\Stock::with('proveedor')->whereNotNull('proveedor_id')->take(1)->get();
foreach ($stocks as $s) {
    $rel = $s->getRelationValue('proveedor');
    echo "Using getRelationValue: " . ($rel ? $rel->nombre_razon_social : 'null') . "\n";
}
