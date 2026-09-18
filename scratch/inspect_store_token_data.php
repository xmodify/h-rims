<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'storeTokenData(Y,j)');
if ($pos === false) $pos = strpos($mainJs, 'storeTokenData(');
echo "=== storeTokenData at $pos ===\n";
echo substr($mainJs, $pos, 800) . "\n";
