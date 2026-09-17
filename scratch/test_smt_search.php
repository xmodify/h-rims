<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$user = DB::table('users')->whereNotNull('eclaim_session_token')->first();
$token = $user ? $user->eclaim_session_token : null;
$hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';

echo "User: " . ($user ? $user->name : 'none') . "\n";
echo "Token: " . $token . "\n\n";

$headers = [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
    'Cookie' => $token,
];

// If token has ACCESS_TOKEN or Bearer
if (preg_match('/ACCESS_TOKEN=([^\s;]+)/i', $token, $m)) {
    $headers['Authorization'] = 'Bearer ' . $m[1];
} elseif (preg_match('/bearer\s+([^\s;]+)/i', $token, $m)) {
    $headers['Authorization'] = 'Bearer ' . $m[1];
}

$payload = [
    'budgetYear' => '2569',
    'vendorId' => '00000' . $hospcode,
    'hcode' => $hospcode,
    'startDate' => '01/09/2569',
    'endDate' => '17/09/2569',
    'page' => 1,
    'size' => 100
];

echo "Testing POST https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search ...\n";
try {
    $res = Http::withHeaders($headers)->withoutVerifying()->timeout(10)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $payload);
    echo "Status: " . $res->status() . "\n";
    echo "Body: " . substr($res->body(), 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
