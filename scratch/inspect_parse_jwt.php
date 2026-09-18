<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'parseJwt(');
while ($pos !== false) {
    if (substr($mainJs, $pos - 1, 1) === ' ' || substr($mainJs, $pos - 1, 1) === '.') {
        echo "=== parseJwt at $pos ===\n";
        echo substr($mainJs, max(0, $pos - 50), 300) . "\n\n";
    }
    $pos = strpos($mainJs, 'parseJwt(', $pos + 1);
}


