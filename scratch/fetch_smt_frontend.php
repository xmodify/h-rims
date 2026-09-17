<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(10)->get('https://smt.nhso.go.th/smtf/');
echo "Status: " . $res->status() . "\n";
$html = $res->body();
echo "Body length: " . strlen($html) . "\n";
preg_match_all('/<script[^>]+src=["\']([^"\']+)["\']/i', $html, $scripts);
print_r($scripts[1]);
