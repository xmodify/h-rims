<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'budget/summary');
while ($pos !== false) {
    echo "--- match 'budget/summary' at $pos ---\n";
    echo substr($js, max(0, $pos - 100), 500) . "\n\n";
    $pos = strpos($js, 'budget/summary', $pos + 20);
    if ($pos > 3000000) break;
}
