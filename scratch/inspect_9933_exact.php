<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js");
$js = $res->body();

$pos = strpos($js, 'getDmisByPerson(');
echo substr($js, max(0, $pos - 400), 1200);
