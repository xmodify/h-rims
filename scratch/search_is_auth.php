<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();
$pos = strpos($c9933, 'isAuth');
while ($pos !== false) {
    echo "=== isAuth in 9933 at $pos ===\n";
    echo substr($c9933, max(0, $pos - 100), 300) . "\n\n";
    $pos = strpos($c9933, 'isAuth', $pos + 1);
}

$c6295 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/6295.7a385a9b7f390beb.js')->body();
$pos2 = strpos($c6295, 'isAuth');
while ($pos2 !== false) {
    echo "=== isAuth in 6295 at $pos2 ===\n";
    echo substr($c6295, max(0, $pos2 - 100), 300) . "\n\n";
    $pos2 = strpos($c6295, 'isAuth', $pos2 + 1);
}
