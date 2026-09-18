<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

foreach (['d13', 'd14', 'd15', 'd16'] as $col) {
    $pos = strpos($js, '"' . $col . '"');
    echo "=== Column {$col} ===\n";
    echo substr($js, max(0, $pos - 100), 500) . "\n\n";
}
