<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'summary-detail');
while ($pos !== false) {
    echo "=== Found summary-detail at $pos ===\n";
    echo substr($js, max(0, $pos - 100), 500) . "\n\n";
    $pos = strpos($js, 'summary-detail', $pos + 1);
}

$pos2 = strpos($js, 'budgetSummaryByVendorReportDetail');
while ($pos2 !== false) {
    echo "=== Found budgetSummaryByVendorReportDetail at $pos2 ===\n";
    echo substr($js, max(0, $pos2 - 100), 500) . "\n\n";
    $pos2 = strpos($js, 'budgetSummaryByVendorReportDetail', $pos2 + 1);
}
