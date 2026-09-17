<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Find route paths in angular
preg_match_all('/path\s*:\s*["\']([^"\']+)["\']/i', $js, $matches);
foreach ($matches[1] as $m) {
    if (stripos($m, 'budget') !== false || stripos($m, 'summary') !== false || stripos($m, 'detail') !== false) {
        echo "Route path: $m\n";
    }
}
