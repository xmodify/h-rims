<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Search for the word "Individual" or "การขอเบิกชดเชย"
preg_match_all('/.{0,100}(?:Individual|การขอเบิกชดเชย|รายงานสรุปการขอเบิก|exportExcel|downloadExcel).{0,100}/u', $js, $matches);

echo "Matches count: " . count($matches[0]) . "\n";
foreach (array_slice($matches[0], 0, 20) as $idx => $m) {
    echo "[$idx] " . trim($m) . "\n";
}

// Search for API base URL (e.g. environment.api, /api/ or backend port/path)
preg_match_all('/"(https?:\/\/[^"]*api[^"]*)"/i', $js, $apis);
print_r(array_unique($apis[1] ?? []));
