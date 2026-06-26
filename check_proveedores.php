<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(App\Models\Proveedor::take(5)->get() as $p) {
    echo "ID: {$p->id} | Name: {$p->nombre_razon_social} | Identificacion: " . ($p->identificacion ? $p->identificacion : 'NULL') . "\n";
}
