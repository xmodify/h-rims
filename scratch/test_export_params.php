<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$jwt = $m[1] ?? null;

$headers = [
    'Authorization' => 'Bearer ' . $jwt,
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

$url = 'https://smt.nhso.go.th/smtf/api/dmis/export/dmis';

$tests = [
    ['vendorId' => '10989'],
    ['refDocNo' => 'DCKD6931080031'],
    ['vendorId' => '10989', 'refDocNo' => 'DCKD6931080031'],
    ['vendorId' => '0000010989', 'refDocNo' => 'DCKD6931080031'],
    ['vendorId' => '10989', 'refDocNo' => 'DCKD6931080031', 'mophId' => '1102050101.216/217'],
];

foreach ($tests as $idx => $t) {
    $res = Http::withHeaders($headers)->withoutVerifying()->post($url, $t);
    echo "DMIS Export Test $idx => Status: " . $res->status() . ", Body length: " . strlen($res->body()) . ", Body: " . substr($res->body(), 0, 100) . "\n";
}

$url2 = 'https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd';
$tests2 = [
    ['vendorId' => '10989'],
    ['recId' => 72668],
    ['recId' => '72668'],
    ['vendorId' => '10989', 'recId' => 72668],
    ['vendorId' => '0000010989', 'recId' => 72668],
    ['vendorId' => '10989', 'recId' => 72668, 'mophId' => '1102050102.801/802'],
];

foreach ($tests2 as $idx => $t) {
    $res = Http::withHeaders($headers)->withoutVerifying()->post($url2, $t);
    echo "LGOHD Export Test $idx => Status: " . $res->status() . ", Body length: " . strlen($res->body()) . ", Body: " . substr($res->body(), 0, 100) . "\n";
}
