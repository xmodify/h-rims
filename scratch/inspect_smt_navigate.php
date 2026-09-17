<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = 0;
while (($pos = strpos($js, 'summary-detail', $pos)) !== false) {
    echo "=== summary-detail usage at $pos ===\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n\n";
    $pos += strlen('summary-detail');
}
