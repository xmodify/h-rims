<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$js = $res->body();

if (preg_match('/3035\s*:\s*["\']([a-f0-9]+)["\']/', $js, $m)) {
    $chunkFile = "3035.{$m[1]}.js";
    echo "Found chunk 3035: $chunkFile\n";
    $resChunk = Http::withoutVerifying()->timeout(15)->get("https://smt.nhso.go.th/smtf/$chunkFile");
    echo "Chunk size: " . strlen($resChunk->body()) . "\n";
    file_put_contents('scratch/chunk_3035.js', $resChunk->body());
} else {
    echo "Chunk 3035 not matched\n";
}
