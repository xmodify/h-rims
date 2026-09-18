<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = 0;
while (($pos = strpos($js, 'budget/summary', $pos)) !== false) {
    echo "Found budget/summary at $pos:\n";
    echo substr($js, max(0, $pos - 150), 350) . "\n----------------\n";
    $pos += 14;
}
