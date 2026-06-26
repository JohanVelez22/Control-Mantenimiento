<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\Stock::where('codigo', 'PRB-3')->first();
echo "ID: {$s->id} | Codigo: {$s->codigo} | Proveedor_id: " . ($s->proveedor_id ? $s->proveedor_id : 'NULL') . " | Raw Proveedor: " . $s->getRawOriginal('proveedor') . "\n";
