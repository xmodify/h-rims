<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

preg_match_all('/[`\'"](?:\/api)?\/(?:dmis|lgohd|adapter|budgetreport)[a-zA-Z0-9_\-\/]*[`\'"]/u', $mJs, $m);
print_r(array_unique($m[0]));
