<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'getDmisByPerson(');
if ($pos !== false) {
    echo "=== getDmisByPerson at $pos ===\n";
    echo substr($mainJs, $pos, 600) . "\n\n";
} else {
    echo "Not found in main.js, searching across all chunks...\n";
    $pos2 = strpos($mainJs, 'report/person/dmis');
    if ($pos2 !== false) {
        echo "Found report/person/dmis at $pos2:\n";
        echo substr($mainJs, max(0, $pos2 - 200), 500) . "\n\n";
    }
}
