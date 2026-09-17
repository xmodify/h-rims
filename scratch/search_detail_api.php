<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

echo "JS length: " . strlen($js) . " bytes\n";

// Search for export / download / individual / detail endpoints
preg_match_all('/(api\/[a-zA-Z0-9_\/]+(download|export|detail|individual|patient)[a-zA-Z0-9_\/]*)/i', $js, $matches);
print_r(array_unique($matches[0]));

// Search for summary-detail API call
$pos = strpos($js, 'budgetSummaryByVendorReportDetail');
while ($pos !== false) {
    echo "--- match 'budgetSummaryByVendorReportDetail' at $pos ---\n";
    echo substr($js, max(0, $pos - 150), 350) . "\n\n";
    $pos = strpos($js, 'budgetSummaryByVendorReportDetail', $pos + 30);
}

$pos = strpos($js, 'budgetSummaryDetail');
while ($pos !== false) {
    echo "--- match 'budgetSummaryDetail' at $pos ---\n";
    echo substr($js, max(0, $pos - 150), 350) . "\n\n";
    $pos = strpos($js, 'budgetSummaryDetail', $pos + 30);
}
