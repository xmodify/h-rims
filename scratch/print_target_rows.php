<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$bearerToken = $m[1] ?? null;

$postData = [
    'vendorSearchConditionCode' => '1',
    'zoneId' => '',
    'provinceId' => '',
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
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(15)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

$data = $res->json();
$datas = $data['datas'] ?? [];

echo "Total rows: " . count($datas) . "\n\n";

foreach ($datas as $row) {
    $refDocNo = $row['refDocNo'] ?? '';
    if (strpos($refDocNo, 'DCKD') !== false || strpos($refDocNo, 'LGO-HD') !== false) {
        echo "=== ROW: " . $refDocNo . " (Batch: " . ($row['batchNo'] ?? '') . ") ===\n";
        print_r($row);
        echo "\n";
    }
}
