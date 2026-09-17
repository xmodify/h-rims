<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$js = $res->body();

preg_match('/8402\s*:\s*["\']([^"\']+)["\']/', $js, $m);
print_r($m);

echo substr($js, 0, 1000);
