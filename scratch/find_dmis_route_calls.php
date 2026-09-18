<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = 0;
while (($pos = strpos($js, 'summary-detail-dmis', $pos)) !== false) {
    echo "=== summary-detail-dmis ===\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n\n";
    $pos += 19;
}

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();
$posM = 0;
while (($posM = strpos($mJs, 'summary-detail-dmis', $posM)) !== false) {
    echo "=== summary-detail-dmis in main ===\n";
    echo substr($mJs, max(0, $posM - 200), 500) . "\n\n";
    $posM += 19;
}
