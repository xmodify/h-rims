<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9050.dd6372a3a88aca37.js');
$js = $cRes->body();

$pos = strpos($js, 'getLgoHdByPerson(');
while ($pos !== false) {
    echo substr($js, max(0, $pos - 100), 500) . "\n-------------------------\n";
    $pos = strpos($js, 'getLgoHdByPerson(', $pos + 1);
}
