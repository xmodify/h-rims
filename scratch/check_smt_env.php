<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Look for environment.apiUrl or baseURL or http:// or https:// in environment definitions
preg_match_all('/(?:apiUrl|baseUrl|apiEndpoint|serviceUrl|url)\s*:\s*["\']([^"\']+)["\']/i', $js, $m);
echo "API URLs:\n";
print_r(array_unique($m[1]));

// Look for budgetSummaryByVendorReport/search context
$pos = strpos($js, 'budgetSummaryByVendorReport/search');
if ($pos !== false) {
    echo "\nContext around budgetSummaryByVendorReport/search:\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n";
}
