<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

$pos = strpos($js, 'openDmis');
if ($pos !== false) {
    echo "=== openDmis ===\n";
    echo substr($js, max(0, $pos - 200), 1000) . "\n\n";
} else {
    echo "openDmis not found, searching for dmis...\n";
    $pos = strpos($js, 'summary-detail-dmis');
    echo substr($js, max(0, $pos - 200), 1000) . "\n\n";
}
