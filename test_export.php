<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::first();
Illuminate\Support\Facades\Auth::login($user);

$request = Illuminate\Http\Request::create('/mantenimientos/reportes', 'GET', ['export' => 'excel']);
$request->setUserResolver(function() use ($user) { return $user; });

$controller = $app->make(App\Http\Controllers\MantenimientoController::class);
$response = $controller->reportes($request);

echo "=== RESPONSE TYPE ===\n";
echo get_class($response) . "\n\n";
echo "=== HEADERS ===\n";
foreach ($response->headers->all() as $key => $val) {
    echo "$key: " . implode(', ', $val) . "\n";
}
echo "\n=== CONTENT SAMPLE ===\n";
$content = $response->getContent();
echo "Length: " . strlen($content) . " bytes\n";
// Show first bytes in hex to identify file type
$hex = bin2hex(substr($content, 0, 8));
echo "Magic bytes: $hex\n";
// PK = 504b0304 = ZIP/XLSX
// %PDF = 25504446 = PDF
