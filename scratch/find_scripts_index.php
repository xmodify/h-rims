<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/index.html');
$html = $res->body();
preg_match_all('/<script[^>]+src="([^">]+)"/i', $html, $m);
print_r($m[1]);
