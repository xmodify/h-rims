<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
$c = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js')->body();


function showFunc($c, $name) {
    $pos = strpos($c, 'function ' . $name . '(');
    if ($pos !== false) {
        echo "=== FUNCTION $name ===\n";
        echo substr($c, $pos, 2500) . "\n\n";
    }
}

showFunc($c, 'V');
showFunc($c, 'G');
showFunc($c, 'q');

$pos = strpos($c, 'checkDownloadDisplay(');
if ($pos !== false) {
    echo "=== checkDownloadDisplay ===\n";
    echo substr($c, $pos, 1500) . "\n\n";
}

