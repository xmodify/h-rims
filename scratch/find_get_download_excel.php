<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = 0;
while (($pos = strpos($js, 'getDownloadExcel', $pos)) !== false) {
    echo "=== getDownloadExcel in template/code ===\n";
    echo substr($js, max(0, $pos - 150), 400) . "\n\n";
    $pos += 16;
}
