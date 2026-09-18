<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

preg_match_all('/([a-zA-Z0-9_\$]+\s*\([^\)]*\)\s*\{[^\}]*dialog\.open[^\}]*\})/u', $js, $m);
print_r($m[0]);

// Also look for calls to getBudgetSummaryDetail in 2153
$pos = 0;
while (($pos = strpos($js, 'getBudgetSummaryDetail', $pos)) !== false) {
    echo "=== getBudgetSummaryDetail occurrence ===\n";
    echo substr($js, max(0, $pos - 200), 600) . "\n\n";
    $pos += 22;
}
