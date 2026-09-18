<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing http://seamlessfordmis.nhso.go.th ===\n";
try {
    $res = Http::withoutVerifying()->timeout(10)->get('http://seamlessfordmis.nhso.go.th');
    echo "Status: " . $res->status() . "\n";
    echo "Headers: " . json_encode($res->headers()) . "\n";
    echo "Body: " . substr($res->body(), 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing https://seamlessfordmis.nhso.go.th ===\n";
try {
    $res2 = Http::withoutVerifying()->timeout(10)->get('https://seamlessfordmis.nhso.go.th');
    echo "Status: " . $res2->status() . "\n";
    echo "Headers: " . json_encode($res2->headers()) . "\n";
    echo "Body: " . substr($res2->body(), 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
