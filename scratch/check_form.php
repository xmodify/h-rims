<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find occurrences of "budgetSummaryByVendorReport"
preg_match_all('/([a-zA-Z0-9_$]+)\.[a-zA-Z0-9_$]+\([^)]*\)[^{]*\{[^}]*budgetSummaryByVendorReport/i', $js, $m);
print_r($m[0]);

// Find all occurrences of form controls or fields around budgetSummaryByVendorReport
$pos = strpos($js, 'budgetSummaryByVendorReport/search');
while ($pos !== false) {
    echo "--- match at $pos ---\n";
    echo substr($js, max(0, $pos - 400), 800) . "\n\n";
    $pos = strpos($js, 'budgetSummaryByVendorReport/search', $pos + 30);
}
