<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find where getBudgetSummary is called or where budgetSummaryByVendorReport is used
preg_match_all('/([a-zA-Z0-9_$]+)\s*:\s*\{[^}]*vendor[^}]*\}/i', $js, $m);
$pos = 0;
while (($pos = strpos($js, 'getBudgetSummary', $pos)) !== false) {
    echo "--- getBudgetSummary at $pos ---\n";
    echo substr($js, max(0, $pos - 300), 800) . "\n\n";
    $pos += 20;
    if ($pos > 2000000) break;
}
