<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

$pos = strpos($mJs, 'summary-detail');
while ($pos !== false) {
    echo "=== summary-detail route ===\n";
    echo substr($mJs, max(0, $pos - 100), 400) . "\n\n";
    $pos = strpos($mJs, 'summary-detail', $pos + 15);
}
