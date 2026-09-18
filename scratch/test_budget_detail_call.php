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

echo "Calling /budgetreport/budgetSummaryByVendorReportDetail...\n";

// In user's screenshot: Batch 2827, 2812, 2888, 2783
// For batch 2783: transfer date is 17 ส.ค. 2569 (2026-08-17), vendor 10989
$postData = [
    'fiscalYear' => '2569',
    'vendorId' => '10989',
    'postingDate' => '2026-08-17',
    'batchNo' => '2783',
    'offset' => 0,
    'limit' => 50,
];

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(15)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', $postData);

echo "Status: " . $res->status() . "\n";
echo "Response: " . substr($res->body(), 0, 1000) . "\n";
