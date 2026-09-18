<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

foreach (['getLgoHdByPersonExcel', 'getDmisByPersonExcel', 'getLgohdByPersonExcel'] as $fn) {
    $pos = strpos($mJs, $fn);
    if ($pos !== false) {
        echo "=== {$fn} definition in main.js ===\n";
        echo substr($mJs, $pos, 400) . "\n\n";
    } else {
        echo "{$fn} not found in main.js\n";
    }
}
