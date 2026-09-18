<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c9933 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js')->body();

$pos = strpos($c9933, 'convertPDFToBase64(n){');
if ($pos === false) $pos = strpos($c9933, 'convertPDFToBase64(');
echo substr($c9933, $pos, 600) . "\n\n";
