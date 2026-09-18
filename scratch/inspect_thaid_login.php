<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'thaidRedirectUri');
echo substr($mainJs, $pos, 800) . "\n\n";

$pos2 = strpos($mainJs, 'thaid-callback');
while ($pos2 !== false) {
    echo "=== thaid-callback at $pos2 ===\n";
    echo substr($mainJs, max(0, $pos2 - 100), 300) . "\n\n";
    $pos2 = strpos($mainJs, 'thaid-callback', $pos2 + 1);
}
