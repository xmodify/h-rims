<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = strpos($js, 'app-budget-summary-detail-dialog');
if ($pos !== false) {
    echo "=== Dialog caller in 2153 ===\n";
    echo substr($js, max(0, $pos - 400), 1000) . "\n\n";
}

$pos2 = strpos($js, 'BudgetSummaryDetailDialog');
if ($pos2 !== false) {
    echo "=== BudgetSummaryDetailDialog ===\n";
    echo substr($js, max(0, $pos2 - 200), 800) . "\n\n";
}
