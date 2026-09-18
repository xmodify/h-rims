<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'seamlessfordmis');
if ($pos !== false) {
    echo "=== seamlessfordmis in main.js ===\n";
    echo substr($mainJs, max(0, $pos - 200), 500) . "\n\n";
} else {
    echo "Not in main.js\n";
}

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();
$pos = strpos($c9933, 'seamlessfordmis');
if ($pos !== false) {
    echo "=== seamlessfordmis in 9933 ===\n";
    echo substr($c9933, max(0, $pos - 200), 500) . "\n\n";
} else {
    echo "Not in 9933\n";
}
