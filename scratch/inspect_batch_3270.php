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
    'vendorId' => '0000010989',
    'vendorId5Digit' => '',
    'budgetSource' => '',
    'budgetYear' => '2569',
    'transferStartDate' => '01/09/2569',
    'transferEndDate' => '18/09/2569',
    'hospType' => '',
    'isTest' => '',
];

$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

$items = $res->json()['datas'] ?? [];
foreach ($items as $it) {
    if (($it['batchNo'] ?? '') == '3270') {
        echo "Found Batch 3270 in search:\n";
        print_r($it);
    }
}
