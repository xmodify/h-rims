<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

$pos = strpos($mJs, 'getBudgetSummaryDetail(');
if ($pos !== false) {
    echo "=== getBudgetSummaryDetail definition in main ===\n";
    echo substr($mJs, $pos, 500) . "\n\n";
} else {
    echo "Not found in main, searching in all chunks...\n";
}
