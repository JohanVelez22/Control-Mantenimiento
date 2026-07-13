<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pass = 0; $fail = 0;
function ok($m) { global $pass; $pass++; echo "OK: $m\n"; }
function fail($m, $d='') { global $fail; $fail++; echo "FAIL: $m"; if($d) echo " -> $d"; echo "\n"; }
function title($m) { echo "\n=== $m ===\n"; }

use App\Models\Stock, Proveedor, Cliente, Factura, FacturaItem;
use App\Models\MovimientoCaja, ConceptoCaja, Mantenimiento, Abono;
use App\Models\Tecnico, Equipo, User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Stock, Proveedor, Cliente, Factura, FacturaItem;
use App\Models\MovimientoCaja, ConceptoCaja, Mantenimiento, Abono;
use App\Models\Tecnico, Equipo, User;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

$admin = User::where('role','admin')->first();
$tecnico = User::where('role','tecnico')->first();
$cliente = Cliente::first();
$proveedor = Proveedor::first();
$tecnicoM = Tecnico::first();
$equipo = Equipo::first();

echo "Admin: {$admin->name}, Tecnico: {$tecnico->name}\n\n";

$pass = 0; $fail = 0;
function ok($m) { global $pass; $pass++; echo "OK: $m\n"; }
function fail($m, $d='') { global $fail; $fail++; echo "FAIL: $m"; if($d) echo " -> $d"; echo "\n"; }
function title($m) { echo "\n=== $m ===\n"; }

$admin = User::where('role','admin')->first();
$tecnico = User::where('role','tecnico')->first();
$cliente = Cliente::first();
$proveedor = Proveedor::first();
$tecnicoM = Tecnico::first();
$equipo = Equipo::first();

echo "Admin: {$admin->name}, Tecnico: {$tecnico->name}\n\n";

title("1. STOCK SERVICE");
$stock = Stock::first();
$orig = $stock->cantidad;
$svc = new StockService();

$stock = $svc->entrada($stock, 5);
if ($stock->cantidad == $orig + 5) ok("entrada(5) suma"); else fail("entrada", "exp {$orig+5} got {$stock->cantidad}");

$stock = $svc->salida($stock, 3);
if ($stock->cantidad == $orig + 2) ok("salida(3) resta"); else fail("salida", "exp {$orig+2} got {$stock->cantidad}");

try { $svc->salida($stock, 9999); fail("salida 9999 debio fallar"); }
catch (\DomainException $e) { ok("salida insuficiente lanza DomainException"); }

$stock2 = Stock::first(); $orig2 = $stock2->cantidad;
$svc->salida($stock2, $orig2);
try { $svc->salida($stock2, 1); fail("2da salida concurrente debio fallar"); }
catch (\DomainException $e) { ok("Concurrencia: 2da salida bloqueada"); }

echo "\n=== RESULTADO: $pass OK, $fail FALLARON ===\n";
if ($fail > 0) exit(1);
exit(0);