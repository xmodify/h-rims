<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$runtime = $res->body();

preg_match_all('/\d+:"[a-f0-9]+"/i', $runtime, $matches);
echo "Chunks count: " . count($matches[0]) . "\n";
print_r(array_slice($matches[0], 0, 30));
