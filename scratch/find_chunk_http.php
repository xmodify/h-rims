<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

// Find HTTP calls in chunk 2153
preg_match_all('/(?:this\.http|http)\.(?:post|get)\([`"]([^`"]+)[`"]/i', $js, $httpCalls);
print_r(array_unique($httpCalls[1]));
