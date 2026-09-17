<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find T5 export or property
preg_match_all('/([a-zA-Z0-9_$]+)\s*=\s*\{[^}]*T5\s*:\s*([^,}]+)/i', $js, $m);
print_r($m[0]);

preg_match_all('/T5\s*:\s*["\']?([^,"\'}\s]+)["\']?/i', $js, $m2);
print_r($m2);
