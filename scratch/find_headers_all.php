<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

$pos = 0;
while (($pos = strpos($mJs, 'headers.set(', $pos)) !== false) {
    echo "=== headers.set ===\n";
    echo substr($mJs, max(0, $pos - 50), 200) . "\n\n";
    $pos += 12;
}

$pos = 0;
while (($pos = strpos($mJs, 'setHeaders', $pos)) !== false) {
    echo "=== setHeaders ===\n";
    echo substr($mJs, max(0, $pos - 50), 200) . "\n\n";
    $pos += 10;
}
