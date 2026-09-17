<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'getBudgetSummaryDetail');
while ($pos !== false) {
    echo "--- match 'getBudgetSummaryDetail' at $pos ---\n";
    echo substr($js, max(0, $pos - 200), 600) . "\n\n";
    $pos = strpos($js, 'getBudgetSummaryDetail', $pos + 30);
}
