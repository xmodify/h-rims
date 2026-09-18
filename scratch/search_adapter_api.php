<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$terms = ['getDmisByPersonExcel', 'getLgohdByPersonExcel', 'getDmisByPerson', 'getLgohdByPerson'];

foreach ($terms as $t) {
    $pos = strpos($mainJs, $t);
    if ($pos !== false) {
        echo "=== $t in main.js ===\n";
        echo substr($mainJs, max(0, $pos - 100), 600) . "\n\n";
    } else {
        echo "=== $t NOT in main.js ===\n";
    }
}
