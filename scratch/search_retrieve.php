<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, '.retrieve(');
while ($pos !== false) {
    echo "=== retrieve at $pos ===\n";
    echo substr($mainJs, max(0, $pos - 100), 300) . "\n\n";
    $pos = strpos($mainJs, '.retrieve(', $pos + 1);
    if ($pos > 300000) break;
}
