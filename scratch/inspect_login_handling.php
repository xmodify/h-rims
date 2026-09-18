<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

echo "=== lines around 834500 ===\n";
echo substr($mainJs, 834000, 1500) . "\n\n";

echo "=== lines around 838500 ===\n";
echo substr($mainJs, 838000, 1500) . "\n\n";
