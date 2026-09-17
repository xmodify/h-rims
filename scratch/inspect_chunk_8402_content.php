<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

echo "Length: " . strlen($js) . "\n";

// Search for route params / paramMap / snapshot
preg_match_all('/(?:paramMap|params|snapshot)[^;]+;/i', $js, $m);
print_r(array_slice($m[0], 0, 10));

// Search for api calls in this chunk
preg_match_all('/(?:http\.post|http\.get)[^)]+\)/i', $js, $m2);
print_r($m2[0]);

// Find all occurrences of download or report
preg_match_all('/[a-zA-Z0-9_\$]+\.[a-zA-Z0-9_\$]+\([^\)]*\)\s*\{[^}]*download[^}]*\}/i', $js, $m3);
print_r($m3[0]);
