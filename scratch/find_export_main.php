<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

preg_match_all('/export[a-zA-Z0-9_]*\s*\(.{0,100}\)\s*\{[^}]*\}/u', $js, $matches);
echo "Export methods in main.js:\n";
foreach (array_slice($matches[0], 0, 15) as $m) {
    echo $m . "\n---\n";
}
