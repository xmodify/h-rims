<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

$pos = strpos($js, 'headerColumns');
while ($pos !== false) {
    echo "=== headerColumns in 8402 ===\n";
    echo substr($js, max(0, $pos - 50), 300) . "\n\n";
    $pos = strpos($js, 'headerColumns', $pos + 13);
}
