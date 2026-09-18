<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

$pos = strpos($js, 'MasterDataApiService');
if ($pos === false) {
    $pos = strpos($js, 'masterDataApiService');
}

while ($pos !== false) {
    echo substr($js, max(0, $pos - 100), 500) . "\n-------------------------\n";
    $pos = strpos($js, 'MasterDataApiService', $pos + 1);
    if (!$pos) $pos = strpos($js, 'masterDataApiService', $pos + 1);
}
