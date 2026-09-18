<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(3);
auth()->login($u);

$controller = app()->make(App\Http\Controllers\SmartMoneyController::class);
$ref = new ReflectionMethod($controller, 'getActiveSmartMoneyToken');
$ref->setAccessible(true);
$tok = $ref->invoke($controller);

echo "Token from getActiveSmartMoneyToken:\n";
echo substr($tok, 0, 80) . "...\n";

$parts = explode('.', $tok);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
echo "payload:\n";
print_r($payload);
