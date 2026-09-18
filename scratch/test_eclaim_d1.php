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

// Test E-CLAIM-D1
$res = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/eclaim-d1', [
    'refDocNo' => '6908_IP_02',
    'recId' => 72783,
    'vendorId' => '10989',
    'batchNo' => 3271,
    'postingDate' => '25690915',
]);

echo "E-CLAIM-D1 status: " . $res->status() . "\n";
echo "Body length: " . strlen($res->body()) . "\n";
echo "Body preview: " . substr($res->body(), 0, 300) . "\n";
