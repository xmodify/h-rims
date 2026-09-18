<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

preg_match_all('/`\${[a-zA-Z0-9_\.]+}\/([a-zA-Z0-9_\-\/]+)`/', $mainJs, $m);
$urls = array_unique($m[1]);
sort($urls);
foreach ($urls as $u) {
    if (strpos($u, 'adapter') !== false || strpos($u, 'dmis') !== false || strpos($u, 'lgohd') !== false || strpos($u, 'lgo-hd') !== false) {
        echo "$u\n";
    }
}
