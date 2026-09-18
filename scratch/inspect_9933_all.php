<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();

// Print constructor and all methods of the main component in 9933
$pos = strpos($c9933, 'displayedColumns=["rownum"');
echo substr($c9933, max(0, $pos - 300), 3000) . "\n\n";
