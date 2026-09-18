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

echo "User: " . ($user->name ?? 'Unknown') . "\n";
echo "Bearer Token: " . substr($bearerToken ?? '', 0, 30) . "...\n\n";

// 1. Call rep-all-host
echo "--- 1. Calling /fintrack/rep-all-host ---\n";
$hostRes = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/fintrack/rep-all-host', (object)[]);

echo "Status: " . $hostRes->status() . "\n";
$hosts = $hostRes->json();
print_r($hosts);

// 2. Call /budgetreport/budgetSummaryByVendorReportDetail for Batch 3270 (DCKD6931080031)
echo "\n--- 2. Calling /budgetreport/budgetSummaryByVendorReportDetail for Batch 3270 ---\n";
$detailRes = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '15/09/2569',
    'batchNo' => '3270',
    'offset' => 0,
    'count' => 50,
    'isTest' => '',
]);

echo "Status: " . $detailRes->status() . "\n";
$detailData = $detailRes->json();
echo "VendorName: " . ($detailData['vendorName'] ?? '') . "\n";
echo "BatchNo: " . ($detailData['batchNo'] ?? '') . "\n";
echo "DisplayDate: " . ($detailData['displayDate'] ?? '') . "\n";
echo "DisplayFileDownload2: " . ($detailData['displayFileDownload2'] ?? '') . "\n";
echo "Items count: " . count($detailData['datas'] ?? []) . "\n";
foreach ($detailData['datas'] ?? [] as $i => $row) {
    echo "  [{$i}] refDocNo: " . ($row['refDocNo'] ?? '') . ", fileType: " . ($row['fileType'] ?? '') . ", fileType2: " . ($row['fileType2'] ?? '') . "\n";
    echo "      hasAttachLvBookNo: " . ($row['hasAttachLvBookNo'] ?? '') . "\n";
    if (!empty($row['downloadDetailLvBookNo'])) {
        print_r($row['downloadDetailLvBookNo']);
    }
    print_r($row);
}
