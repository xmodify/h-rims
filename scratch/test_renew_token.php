<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$jwt = $m[1];

echo "Testing /renew-token with ACCESS_TOKEN...\n";
$res = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/renew-token', [
    'token' => $jwt
]);

echo "Status: " . $res->status() . "\n";
echo "Body: " . $res->body() . "\n";

// Also test with KEYCLOAK_IDENTITY
preg_match('/KEYCLOAK_IDENTITY=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m2);
$kId = $m2[1];
echo "\nTesting /renew-token with KEYCLOAK_IDENTITY...\n";
$res2 = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/renew-token', [
    'token' => $kId
]);
echo "Status: " . $res2->status() . "\n";
echo "Body: " . $res2->body() . "\n";
