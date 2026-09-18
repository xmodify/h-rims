<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/3035.8e603078840052bf.js');
$cJs = $cRes->body();

preg_match_all('/["\']([\/a-zA-Z0-9_\-\.\?=\&]+)["\']/u', $cJs, $matches);
$paths = array_filter($matches[1], fn($p) => str_starts_with($p, '/') || str_contains($p, 'search') || str_contains($p, 'report') || str_contains($p, 'list'));
print_r(array_unique($paths));
