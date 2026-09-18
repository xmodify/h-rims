<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== FETCHING CHUNK 9933 (DMIS) ===\n";
$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();

echo "Length 9933: " . strlen($c9933) . "\n";

// Search for http requests, export, excel, download, api in 9933
echo "=== 9933 exportExcel ===\n";
$pos = strpos($c9933, 'exportExcel');
while ($pos !== false) {
    echo substr($c9933, max(0, $pos - 200), 1200) . "\n---\n";
    $pos = strpos($c9933, 'exportExcel', $pos + 1);
}

$c6295 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/6295.7a385a9b7f390beb.js')->body();

echo "\n=== 6295 exportExcel ===\n";
$pos = strpos($c6295, 'exportExcel');
while ($pos !== false) {
    echo substr($c6295, max(0, $pos - 200), 1200) . "\n---\n";
    $pos = strpos($c6295, 'exportExcel', $pos + 1);
}

while ($pos !== false) {
    echo substr($c6295, max(0, $pos - 200), 1200) . "\n---\n";
    $pos = strpos($c6295, 'exportExcel', $pos + 1);
}

