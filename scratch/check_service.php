<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'getBudgetSummary(f){return this.http.post(`${l.T5}/budgetreport/budgetSummaryByVendorReport/search`,f)}');
if ($pos !== false) {
    // Find the end of this service class and its factory / token
    echo substr($js, $pos, 1500) . "\n";
}
