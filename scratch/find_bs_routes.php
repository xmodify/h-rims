<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Search for path 'bs' in routes
preg_match_all('/path\s*:\s*"bs[^"]*".{0,500}/u', $js, $matches);
echo "Routes matching 'bs':\n";
foreach ($matches[0] as $idx => $m) {
    echo "[$idx] " . trim($m) . "\n";
}

// Search for any occurrence of "bs/" or "/bs"
preg_match_all('/["\'](?:\/?[a-zA-Z0-9_\-]*bs[a-zA-Z0-9_\-\/]*)["\']/i', $js, $bsWords);
print_r(array_unique($bsWords[0] ?? []));
