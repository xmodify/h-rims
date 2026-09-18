<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'getLgohdByPerson(');
echo "=== getLgohdByPerson ===\n";
echo substr($mainJs, $pos, 500) . "\n\n";

$pos = strpos($mainJs, '92340:');
echo "=== module 92340 ===\n";
echo substr($mainJs, $pos, 1000) . "\n\n";


