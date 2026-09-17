<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find all occurrences of budgetSummaryByVendorReportDetail
$offset = 0;
while (($pos = strpos($js, 'budgetSummaryByVendorReportDetail', $offset)) !== false) {
    echo "--- Match at $pos ---\n";
    echo substr($js, max(0, $pos - 250), 600) . "\n\n";
    $offset = $pos + 35;
}
