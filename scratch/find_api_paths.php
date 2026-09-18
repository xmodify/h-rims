<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = strpos($js, 'getRepAllHostForExcel');
if ($pos !== false) {
    echo "=== getRepAllHostForExcel in 2153 ===\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n\n";
}

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

$posM = strpos($mJs, 'getRepAllHostForExcel');
if ($posM !== false) {
    echo "=== getRepAllHostForExcel in main ===\n";
    echo substr($mJs, max(0, $posM - 200), 500) . "\n\n";
}

$posD = strpos($js, 'getBudgetSummaryDetail(');
if ($posD === false) {
    $posD = strpos($mJs, 'getBudgetSummaryDetail(');
}
if ($posD !== false) {
    echo "=== getBudgetSummaryDetail ===\n";
    echo substr($js . $mJs, max(0, $posD - 100), 500) . "\n\n";
}
