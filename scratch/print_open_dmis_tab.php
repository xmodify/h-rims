<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

$pos = strpos($js, 'openDmisNewTab(');
while ($pos !== false) {
    echo "=== openDmisNewTab at $pos ===\n";
    echo substr($js, $pos, 800) . "\n\n";
    $pos = strpos($js, 'openDmisNewTab(', $pos + 1);
}
