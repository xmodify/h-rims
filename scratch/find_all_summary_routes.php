<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find route configuration containing "budget"
$pos = 0;
while (($pos = strpos($js, 'path:"summary"', $pos)) !== false) {
    echo "Summary route context:\n";
    echo substr($js, max(0, $pos - 200), 600) . "\n----------------\n";
    $pos += 14;
}
