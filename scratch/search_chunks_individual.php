<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$runtime = $res->body();
preg_match_all('/(\d+):"([a-f0-9]+)"/i', $runtime, $matches, PREG_SET_ORDER);

echo "Searching " . count($matches) . " chunks for 'Individual'...\n";

foreach ($matches as $m) {
    $chk = $m[1];
    $hash = $m[2];
    $url = "https://smt.nhso.go.th/smtf/{$chk}.{$hash}.js";
    try {
        $c = Http::withoutVerifying()->timeout(5)->get($url);
        if (str_contains($c->body(), 'Individual') || str_contains($c->body(), 'individual') || str_contains($c->body(), 'การขอเบิกชดเชย')) {
            echo "MATCH in chunk {$chk} ({$url})\n";
        }
    } catch (\Exception $e) {}
}
