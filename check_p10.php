<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Proveedor::find(10);
if ($p) {
    echo "Found! Name: " . $p->nombre_razon_social . " | Nit: " . $p->identificacion;
} else {
    echo "Not found.";
}
