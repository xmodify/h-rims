<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'getDmisByPersonExcel');
if ($pos !== false) {
    echo "=== getDmisByPersonExcel ===\n";
    echo substr($mainJs, $pos - 50, 400) . "\n\n";
}

$pos2 = strpos($mainJs, 'getLgoHdByPersonExcel');
if ($pos2 !== false) {
    echo "=== getLgoHdByPersonExcel ===\n";
    echo substr($mainJs, $pos2 - 50, 400) . "\n\n";
}
