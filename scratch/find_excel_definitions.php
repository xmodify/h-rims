<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

foreach (['getDmisByPersonExcel', 'getLgoHdByPersonExcel'] as $fn) {
    echo "=== $fn ===\n";
    $pos = strpos($mainJs, $fn . '(');
    if ($pos !== false) {
        echo substr($mainJs, $pos, 400) . "\n\n";
    } else {
        echo "Not found in main.js\n";
    }
}
