<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = 0;
while (($pos = strpos($mainJs, 'Unauthorized', $pos)) !== false) {
    echo "=== Unauthorized at $pos ===\n";
    echo substr($mainJs, max(0, $pos - 150), 350) . "\n\n";
    $pos += 12;
}
