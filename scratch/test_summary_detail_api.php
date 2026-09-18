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

// Test Batch 3270 (DCKD6931080031)
echo "=== TESTING BATCH 3270 (DCKD) ===\n";
$res1 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '25690915',
    'batchNo' => 3270,
    'offset' => 0,
    'count' => 10,
    'isTest' => '',
]);
echo "Status: " . $res1->status() . "\n";
$data1 = $res1->json();
echo "Status field: " . ($data1['status'] ?? 'ok') . "\n";
echo "Count: " . ($data1['count'] ?? 0) . "\n";
if (!empty($data1['datas'])) {
    foreach ($data1['datas'] as $row) {
        echo "Row: " . ($row['refDocNo'] ?? '') . " | fromSystem: " . ($row['fromSystem'] ?? '') . " | linkConfigId: " . ($row['linkConfigId'] ?? '') . "\n";
        if (!empty($row['downloadDetailLvBookNo'])) {
            echo "downloadDetailLvBookNo: " . json_encode($row['downloadDetailLvBookNo'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "Full row 0:\n";
    print_r($data1['datas'][0]);
}

// Test Batch 3150 (LGO-HD69-M11)
echo "\n=== TESTING BATCH 3150 (LGO-HD) ===\n";
$res2 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '25690911',
    'batchNo' => 3150,
    'offset' => 0,
    'count' => 10,
    'isTest' => '',
]);
echo "Status: " . $res2->status() . "\n";
$data2 = $res2->json();
echo "Status field: " . ($data2['status'] ?? 'ok') . "\n";
echo "Count: " . ($data2['count'] ?? 0) . "\n";
if (!empty($data2['datas'])) {
    foreach ($data2['datas'] as $row) {
        echo "Row: " . ($row['refDocNo'] ?? '') . " | fromSystem: " . ($row['fromSystem'] ?? '') . " | recId: " . ($row['recId'] ?? '') . " | linkConfigId: " . ($row['linkConfigId'] ?? '') . "\n";
        if (!empty($row['downloadDetailLvBookNo'])) {
            echo "downloadDetailLvBookNo: " . json_encode($row['downloadDetailLvBookNo'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "Full row 0:\n";
    print_r($data2['datas'][0]);
}
