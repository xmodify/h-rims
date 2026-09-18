<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);

$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $m[1],
    'Content-Type' => 'application/json',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '25690915',
    'batchNo' => '3270',
    'offset' => 0,
    'count' => 10,
    'isTest' => '',
]);
$json = $res->json();
print_r(array_keys($json));
if (!empty($json['datas'])) {
    echo "First data item in batch 3270:\n";
    print_r($json['datas'][0]);
}
