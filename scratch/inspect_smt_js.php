<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();
echo "JS size: " . strlen($js) . "\n";

// Find all api / endpoints in the JS
preg_match_all('/["\'](api\/[a-zA-Z0-9_\-\/]+)["\']/i', $js, $matches);
$apis = array_unique($matches[1]);
echo "Found " . count($apis) . " API paths:\n";
foreach ($apis as $api) {
    echo "  - $api\n";
}

// Find all POST / GET calls with budget or transfer
preg_match_all('/(https?:\/\/[^\s"\']+|api\/[a-zA-Z0-9_\-\/]+|budgetreport[^\s"\']+)/i', $js, $matches2);
$filtered = array_filter(array_unique($matches2[0]), fn($u) => stripos($u, 'budget') !== false || stripos($u, 'smt') !== false || stripos($u, 'transfer') !== false || stripos($u, 'report') !== false);
echo "\nInteresting URLs / Endpoints:\n";
foreach (array_slice($filtered, 0, 30) as $u) {
    echo "  * $u\n";
}
