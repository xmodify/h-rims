<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
$c = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js')->body();

$terms = ['openDmisNewTab', 'openLgoHdApNewTab', 'openLgoHdNewTab', 'checkDownloadDisplay', 'checkDownloadDisplay2'];

$offset = 0;
while (($pos = strpos($c, 'openDmisNewTab(', $offset)) !== false) {
    echo "=== openDmisNewTab( at $pos ===\n";
    echo substr($c, $pos, 600) . "\n\n";
    $offset = $pos + 1;
}

$offset = 0;
while (($pos = strpos($c, 'openLgoHdNewTab(', $offset)) !== false) {
    echo "=== openLgoHdNewTab( at $pos ===\n";
    echo substr($c, $pos, 600) . "\n\n";
    $offset = $pos + 1;
}


