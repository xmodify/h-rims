<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find where getBudgetSummary( is called
$pos = 0;
while (($pos = strpos($js, '.getBudgetSummary(', $pos)) !== false) {
    echo "--- call at $pos ---\n";
    echo substr($js, max(0, $pos - 500), 1000) . "\n\n";
    $pos += 20;
    if ($pos > 3000000) break;
}
