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

$headers = [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
    'Cookie' => $token,
];

if (preg_match('/ACCESS_TOKEN=([^\s;]+)/i', $token, $m)) {
    $headers['Authorization'] = 'Bearer ' . $m[1];
}

$endpoints = [
    '/fintrack/overview' => ['hcode' => $hospcode, 'budgetYear' => 2569],
    '/fintrack/list/all' => ['hcode' => $hospcode, 'budgetYear' => 2569],
    '/fintrack/list/small' => ['hcode' => $hospcode, 'budgetYear' => 2569],
    '/budget/overview/search' => ['hcode' => $hospcode, 'budgetYear' => 2569],
    '/budgetreport/budgetSummaryByVendorReportDetail' => ['hcode' => $hospcode, 'budgetYear' => 2569],
];

foreach ($endpoints as $ep => $data) {
    $url = "https://smt.nhso.go.th/smtf/api" . $ep;
    try {
        $res = Http::withHeaders($headers)->withoutVerifying()->timeout(10)->post($url, $data);
        echo "POST $ep -> Status: " . $res->status() . " (Size: " . strlen($res->body()) . ")\n";
        echo "Response: " . substr($res->body(), 0, 300) . "\n\n";
    } catch (\Exception $e) {
        echo "POST $ep -> Error: " . $e->getMessage() . "\n\n";
    }
}
