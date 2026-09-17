<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'path:"budget"');
if ($pos === false) $pos = strpos($js, 'path:"summary"');
while ($pos !== false) {
    echo "--- match at $pos ---\n";
    echo substr($js, max(0, $pos - 50), 300) . "\n\n";
    $pos = strpos($js, 'path:"summary"', $pos + 20);
    if ($pos > 3000000) break;
}
