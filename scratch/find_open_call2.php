<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

preg_match_all('/\.open\([a-zA-Z0-9_\$,\s\{\}:\'"\(\)]+?\)/u', $js, $m);
foreach ($m[0] as $call) {
    if (str_contains($call, 'fiscal') || str_contains($call, 'batch') || str_contains($call, 'posting')) {
        echo $call . "\n\n";
    }
}
