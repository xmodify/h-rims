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

// The downloadPAYMFileName from batch 2888
$fileName = "3013273FDDA8C83A8BA323E115A9202C6AD3D8F7219A607D6BB0CED25AB695B5536475F0C0A33B7728DE4EED5A2E3B964A9244D46BDCF6945BC608C269EF4DD0";

$url = "https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail/download?fileName=" . urlencode($fileName);

echo "Testing download from: {$url}\n";

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Authorization' => 'Bearer ' . $bearerToken,
    'Accept' => '*/*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(20)->get($url);

echo "Status: " . $res->status() . "\n";
echo "Content-Type: " . $res->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res->header('Content-Disposition') . "\n";
echo "Length: " . strlen($res->body()) . " bytes\n";
