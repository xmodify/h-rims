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

$batches = [
    ['batchNo' => 3292, 'postingDate' => '25690918'],
    ['batchNo' => 3289, 'postingDate' => '25690917'],
    ['batchNo' => 3286, 'postingDate' => '25690917'],
    ['batchNo' => 3271, 'postingDate' => '25690915'],
];

foreach ($batches as $b) {
    $res = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
        'fiscalYear' => '2569',
        'vendorId' => '0000010989',
        'postingDate' => $b['postingDate'],
        'batchNo' => $b['batchNo'],
        'offset' => 0,
        'count' => 10,
        'isTest' => '',
    ]);
    echo "=== BATCH {$b['batchNo']} ===\n";
    $datas = $res->json('datas') ?? [];
    foreach ($datas as $row) {
        echo "Ref: " . ($row['refDocNo'] ?? '') . " | Fund: " . ($row['fundName'] ?? '') . " | fromSystem: " . ($row['fromSystem'] ?? '') . " | recId: " . ($row['recId'] ?? '') . " | linkConfigId: " . ($row['linkConfigId'] ?? '') . "\n";
    }
}
