<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find definition of T5 or environment variables
preg_match_all('/T5\s*:\s*["\']([^"\']+)["\']/i', $js, $m);
echo "T5 values:\n";
print_r($m);

// Search for `environment = {` or similar
preg_match_all('/(?:environment|env)\s*=\s*\{([^}]+)\}/i', $js, $m2);
echo "Environment blocks:\n";
print_r($m2[0]);

// Find all occurrences of `/budgetreport/`
preg_match_all('/[`"\']([^`"\']*\/budgetreport\/[^`"\']*)[`"\']/i', $js, $m3);
print_r(array_unique($m3[1]));
