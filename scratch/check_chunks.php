<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Look for loadChildren or import(
preg_match_all('/import\(["\']\.\/([^"\']+)["\']\)/i', $js, $m);
print_r($m[1]);

// Look for chunk filenames
preg_match_all('/([0-9]+\.[a-f0-9]+\.js)/i', $js, $m2);
print_r(array_unique($m2[1]));
