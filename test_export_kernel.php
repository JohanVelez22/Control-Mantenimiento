<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::first();
if (!$user) {
    die("No user found.");
}
Auth::login($user);

$request = Illuminate\Http\Request::create('/mantenimientos/reportes?export=excel', 'GET');
$response = $kernel->handle($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "Content-Disposition: " . $response->headers->get('Content-Disposition') . "\n";
echo "Body Size: " . strlen($response->getContent()) . "\n";
echo "Body Snippet: \n" . substr($response->getContent(), 0, 1000) . "\n";
