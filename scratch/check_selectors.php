<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

preg_match_all('/selectors\s*:\s*\[\["([^"]+)"\]\]/i', $js, $m);
$selectors = array_filter(array_unique($m[1]), fn($s) => stripos($s, 'budget') !== false || stripos($s, 'summary') !== false || stripos($s, 'vendor') !== false);
echo "Selectors:\n";
print_r($selectors);
