<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$chunks = ['8402.7c4bc5f84dcbdfa5.js', '2153.fe2a1dd64a0e8426.js', '98246.f4e41ea50e68be51.js'];
foreach ($chunks as $c) {
    $js = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/$c")->body();
    $pos = strpos($js, 'summary-detail-dmis');
    if ($pos !== false) {
        echo "Found in $c at $pos:\n";
        echo substr($js, max(0, $pos - 150), 400) . "\n\n";
    }
}
