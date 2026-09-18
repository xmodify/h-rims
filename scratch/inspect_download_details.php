<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = strpos($js, 'download(){');
if ($pos !== false) {
    echo "=== download() ===\n";
    echo substr($js, $pos, 1500) . "\n\n";
}

$pos2 = strpos($js, 'downloadIPExcel(){');
if ($pos2 !== false) {
    echo "=== downloadIPExcel() ===\n";
    echo substr($js, $pos2, 1500) . "\n\n";
}

$pos3 = strpos($js, 'downloadExcelHost');
if ($pos3 !== false) {
    echo "=== downloadExcelHost context ===\n";
    echo substr($js, max(0, $pos3 - 300), 1000) . "\n\n";
}
