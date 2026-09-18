<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$c8402 = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js')->body();

$pos = strpos($c8402, 'checkDownloadDisplay(e){');
echo "=== checkDownloadDisplay(e) ===\n";
echo substr($c8402, $pos, 2500) . "\n\n";

$pos2 = strpos($c8402, 'checkDownloadDisplay2(e){');
echo "=== checkDownloadDisplay2(e) ===\n";
echo substr($c8402, $pos2, 2500) . "\n\n";
