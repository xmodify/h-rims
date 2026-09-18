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

$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $jwt,
    'Content-Type' => 'application/json',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '25690825',
    'batchNo' => '2888',
    'offset' => 0,
    'count' => 25,
    'isTest' => '',
]);

$datas = $res->json('datas') ?? [];
foreach ($datas as $row) {
    echo "Ref: " . ($row['refDocNo'] ?? '') . " | fromSystem: " . ($row['fromSystem'] ?? '') . " | downloadUrl: " . ($row['downloadUrl'] ?? '') . " | recId: " . ($row['recId'] ?? '') . "\n";
}
