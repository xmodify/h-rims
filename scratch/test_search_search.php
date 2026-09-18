<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m);
$bearerToken = $m[1] ?? null;

$postData = [
    'vendorSearchConditionCode' => '1',
    'zoneId' => '10',
    'provinceId' => '3700',
    'vendorId' => '10989',
    'vendorId5Digit' => '10989',
    'budgetSource' => '',
    'budgetYear' => '2569',
    'transferStartDate' => '2026-08-01',
    'transferEndDate' => '2026-08-31',
    'hospType' => '',
    'isTest' => '0',
    'offset' => 0,
    'limit' => 25,
];

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(15)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

echo "Status: " . $res->status() . "\n";
echo "Response: " . substr($res->body(), 0, 1500) . "\n";
