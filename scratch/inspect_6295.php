<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/6295.7a385a9b7f390beb.js');
$js = $res->body();

$pos = strpos($js, 'ngAfterViewInit');
echo substr($js, $pos, 1500);
