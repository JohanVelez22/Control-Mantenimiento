<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = 0;
foreach(App\Models\Stock::whereNull('proveedor_id')->get() as $s) {
    $rawProveedor = $s->getRawOriginal('proveedor');
    if ($rawProveedor) {
        $p = App\Models\Proveedor::where('nombre_razon_social', $rawProveedor)->first();
        if($p) {
            $s->proveedor_id = $p->id;
            $s->save();
            $count++;
        }
    }
}
echo "Updated $count stocks.";
