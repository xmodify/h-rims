<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find route "summary-detail" or "summary" or "bs"
$pos = strpos($js, 'summary-detail');
while ($pos !== false) {
    echo "--- match 'summary-detail' at $pos ---\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n\n";
    $pos = strpos($js, 'summary-detail', $pos + 20);
    if ($pos > 3000000) break;
}
