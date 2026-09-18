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

echo "=== 1. Option Service Token ===\n";
$res1 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/option/service-token', (object)[]);
echo "Status: " . $res1->status() . "\n";
print_r($res1->json());

echo "\n=== 2. Service Token Filter List ===\n";
$res2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/fintrack/service-token-filter-list', (object)[]);
echo "Status: " . $res2->status() . "\n";
print_r($res2->json());
