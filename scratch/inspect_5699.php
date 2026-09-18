<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$runtime = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js')->body();
preg_match('/5699:\s*"([a-f0-9]+)"/', $runtime, $m);
$hash = $m[1] ?? '';
echo "Chunk 5699 hash: $hash\n";
if ($hash) {
    $c5699 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/5699.{$hash}.js")->body();
    echo "5699 length: " . strlen($c5699) . "\n";
echo substr($c5699, 1500, 3500) . "\n\n";

}

