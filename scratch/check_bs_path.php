<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = 0;
while (($pos = strpos($js, 'path:"bs"', $pos)) !== false || ($pos = strpos($js, "path:'bs'", $pos)) !== false) {
    echo "--- match 'path: bs' at $pos ---\n";
    echo substr($js, max(0, $pos - 100), 400) . "\n\n";
    $pos += 15;
    if ($pos > 3000000) break;
}
