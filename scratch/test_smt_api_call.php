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

echo "Calling https://smt.nhso.go.th/smtf/api with Bearer token...\n";

// Test some common endpoints found earlier:
// /budgetadmin/PaymentDetailAttachment/extension/individual/list
// /user-mangement/fiscal-year-list
// /budgetreport/summaryTransferAndOutstandingLevelReport/search

$endpoints = [
    '/user-mangement/fiscal-year-list' => [],
    '/budgetadmin/PaymentDetailAttachment/extension/individual/list' => [],
    '/configuration/interval-time' => [],
];

foreach ($endpoints as $ep => $postData) {
    try {
        $res = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/plain, */*',
            'Origin' => 'https://smt.nhso.go.th',
            'Referer' => 'https://smt.nhso.go.th/smtf/',
        ])->withoutVerifying()->timeout(10)->post('https://smt.nhso.go.th/smtf/api' . $ep, $postData);

        echo "Endpoint {$ep} -> Status: " . $res->status() . "\n";
        echo "Response snippet: " . substr($res->body(), 0, 300) . "\n\n";
    } catch (\Exception $e) {
        echo "Endpoint {$ep} -> Error: " . $e->getMessage() . "\n\n";
    }
}
