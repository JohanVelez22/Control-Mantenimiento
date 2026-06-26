<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(App\Models\Stock::take(5)->get() as $s) {
    echo "ID: {$s->id} | Codigo: {$s->codigo} | Proveedor_id: {$s->proveedor_id} | Raw Proveedor: " . $s->getRawOriginal('proveedor') . "\n";
}
