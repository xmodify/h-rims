<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find mapping for 8402 or 38402
preg_match_all('/8402\s*:\s*["\']([^"\']+)["\']/i', $js, $m);
print_r($m);

// Find all .js files
preg_match_all('/[a-zA-Z0-9\-\_]+\.[a-f0-9]+\.js/i', $js, $m2);
print_r(array_slice(array_unique($m2[0]), 0, 20));
