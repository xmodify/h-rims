<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();
echo "Length of 8402: " . strlen($js) . " bytes\n";

preg_match_all('/summary-detail[a-zA-Z0-9_\-\/]*/u', $js, $m);
echo "Routes in 8402:\n";
print_r(array_unique($m[0]));

preg_match_all('/(?:router\.navigate|window\.open)\([^\)]+\)/u', $js, $navs);
echo "Navigations in 8402:\n";
print_r(array_unique($navs[0]));
