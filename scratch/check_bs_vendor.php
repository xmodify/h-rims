<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find occurrences of budgetSummaryByVendorReport
$pos = 0;
while (($pos = strpos($js, 'budgetSummaryByVendorReport', $pos)) !== false) {
    echo "--- pos $pos ---\n";
    echo substr($js, max(0, $pos - 100), 400) . "\n\n";
    $pos += 30;
    if ($pos > 4000000) break;
}
