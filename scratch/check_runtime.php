<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$js = $res->body();

echo "Runtime JS size: " . strlen($js) . "\n";
// Find map of chunk IDs to filenames
preg_match_all('/([0-9]+)\s*:\s*["\']([a-f0-9]+)["\']/i', $js, $m);
for ($i = 0; $i < count($m[0]); $i++) {
    echo "Chunk {$m[1][$i]} -> {$m[1][$i]}.{$m[2][$i]}.js\n";
}
