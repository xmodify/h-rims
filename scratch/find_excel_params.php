<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res1 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9050.dd6372a3a88aca37.js');
$js1 = $res1->body();
$pos1 = strpos($js1, 'getLgohdByPersonExcel(');
if ($pos1 !== false) {
    echo "=== getLgohdByPersonExcel in 9050 ===\n";
    echo substr($js1, max(0, $pos1 - 100), 500) . "\n";
}

$res2 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.8e6b12a2a0ebcb02.js');
if (!$res2->successful()) {
    // find chunk 9933 hash
    $rt = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js')->body();
    if (preg_match('/9933:"([a-f0-9]+)"/', $rt, $m)) {
        $res2 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9933.{$m[1]}.js");
    }
}
$js2 = $res2->body();
$pos2 = strpos($js2, 'getDmisByPersonExcel(');
if ($pos2 !== false) {
    echo "=== getDmisByPersonExcel in 9933 ===\n";
    echo substr($js2, max(0, $pos2 - 100), 500) . "\n";
}
