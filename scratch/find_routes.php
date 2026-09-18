<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

foreach (['8402', '9933', '6295'] as $chunk) {
    echo "=== Chunk $chunk ===\n";
    $pos = strpos($mainJs, $chunk);
    while ($pos !== false) {
        echo substr($mainJs, max(0, $pos - 150), 350) . "\n---\n";
        $pos = strpos($mainJs, $chunk, $pos + 1);
        if ($pos > 2000000) break;
    }
}

