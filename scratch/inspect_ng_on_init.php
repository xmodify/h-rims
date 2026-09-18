<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();
$c6295 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/6295.7a385a9b7f390beb.js')->body();

echo "=== 9933 ngOnInit ===\n";
$pos = strpos($c9933, 'ngOnInit');
echo substr($c9933, $pos, 1500) . "\n\n";

echo "=== 6295 ngOnInit ===\n";
$pos = strpos($c6295, 'ngOnInit');
echo substr($c6295, $pos, 1500) . "\n\n";
