<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();

$pos = strpos($c9933, 'ngAfterViewInit');
echo "=== 9933 ngAfterViewInit ===\n";
echo substr($c9933, $pos, 1500) . "\n\n";

$pos2 = strpos($c9933, 'constructor(');
echo "=== 9933 constructor or params ===\n";
echo substr($c9933, max(0, $pos - 1200), 1200) . "\n\n";
