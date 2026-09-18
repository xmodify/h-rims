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

$params = [
    'fiscalYear' => '2569',
    'vendorId' => '0000010989',
    'postingDate' => '25690911',
    'batchNo' => '3150',
    'offset' => 0,
    'count' => 50,
    'isTest' => '',
];

$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', $params);

$configs = $res->json()['linkConfig'] ?? [];
foreach ($configs as $cfg) {
    if (in_array($cfg['linkConfigId'] ?? '', [5, 12, '5', '12']) || str_contains($cfg['name'] ?? '', 'LGO') || str_contains($cfg['name'] ?? '', 'DMIS')) {
        echo "LinkConfig ID {$cfg['linkConfigId']}:\n";
        print_r($cfg);
    }
}
