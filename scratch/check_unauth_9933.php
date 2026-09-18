<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();

$pos = strpos($c9933, 'Unauthorized');
echo "Unauthorized in 9933: " . ($pos !== false ? "YES at $pos" : "NO") . "\n";

$pos2 = strpos($c9933, 'ไม่มีสิทธิ์');
echo "ไม่มีสิทธิ์ in 9933: " . ($pos2 !== false ? "YES at $pos2" : "NO") . "\n";
if ($pos2 !== false) {
    echo substr($c9933, max(0, $pos2 - 100), 300) . "\n";
}
