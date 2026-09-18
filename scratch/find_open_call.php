<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

// find where selector or component class is passed to dialog.open
preg_match_all('/\.open\([a-zA-Z0-9_]+,\s*\{[^\}]*data\s*:\s*\{[^\}]+\}[^\}]*\)/u', $js, $m);
print_r($m[0]);
