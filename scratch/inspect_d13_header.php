<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c8402 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js')->body();

// search for d13, d14, d15 header titles
$pos = strpos($c8402, '"d13"');
while ($pos !== false) {
    echo "=== d13 at $pos ===\n";
    echo substr($c8402, max(0, $pos - 150), 350) . "\n\n";
    $pos = strpos($c8402, '"d13"', $pos + 1);
}
