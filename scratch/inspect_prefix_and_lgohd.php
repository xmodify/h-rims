<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$terms = ['adapter/dmis/prefix', 'report/person/lgo-hd', 'export-excel-lgo-hd-by-person'];

foreach ($terms as $t) {
    $pos = strpos($mainJs, $t);
    if ($pos !== false) {
        echo "=== $t ===\n";
        echo substr($mainJs, max(0, $pos - 150), 500) . "\n\n";
    }
}
