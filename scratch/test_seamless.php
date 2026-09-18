<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('http://seamlessfordmis.nhso.go.th');
echo "Status: " . $res->status() . "\n";
echo "Headers: \n";
print_r($res->headers());
echo "Body: \n";
echo substr($res->body(), 0, 1000) . "\n";
