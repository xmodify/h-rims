<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();
$pos = strpos($c9933, '\u0e44\u0e21\u0e48\u0e21\u0e35\u0e2a\u0e34\u0e17\u0e18\u0e34\u0e4c\u0e40\u0e02\u0e49\u0e32\u0e43\u0e0a\u0e49\u0e07\u0e32\u0e19\u0e23\u0e30\u0e1a\u0e1a');
if ($pos !== false) {
    echo "Found in 9933 at $pos\n";
    echo substr($c9933, max(0, $pos - 150), 300) . "\n\n";
} else {
    echo "Not in 9933\n";
}

$c6295 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/6295.7a385a9b7f390beb.js')->body();
$pos2 = strpos($c6295, '\u0e44\u0e21\u0e48\u0e21\u0e35\u0e2a\u0e34\u0e17\u0e18\u0e34\u0e4c\u0e40\u0e02\u0e49\u0e32\u0e43\u0e0a\u0e49\u0e07\u0e32\u0e19\u0e23\u0e30\u0e1a\u0e1a');
if ($pos2 !== false) {
    echo "Found in 6295 at $pos2\n";
    echo substr($c6295, max(0, $pos2 - 150), 300) . "\n\n";
} else {
    echo "Not in 6295\n";
}
