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

$headers = [
    'Authorization' => 'Bearer ' . $jwt,
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

function tryCall($url, $payload, $label) {
    global $headers;
    echo "=== $label ===\nURL: $url\nPayload: " . json_encode($payload) . "\n";
    $res = Http::withHeaders($headers)->withoutVerifying()->timeout(15)->post($url, $payload);
    echo "Status: " . $res->status() . "\n";
    $body = $res->body();
    echo "Length: " . strlen($body) . "\n";
    if (strlen($body) > 0) {
        echo "Body preview: " . substr($body, 0, 300) . "\n";
    }
    echo "\n";
}

// 1. adapter/report/person/lgo-hd (with hyphen!)
tryCall('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgo-hd', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
], 'adapter/report/person/lgo-hd 5-digit');

tryCall('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgo-hd', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '0000010989',
], 'adapter/report/person/lgo-hd 10-digit');

tryCall('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgo-hd', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
    'recId' => 72668,
    'mophId' => '1102050102.801/802',
], 'adapter/report/person/lgo-hd full');

// 2. adapter/export-excel-lgo-hd-by-person
tryCall('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
], 'adapter/export-excel-lgo-hd-by-person 5-digit');

tryCall('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '0000010989',
], 'adapter/export-excel-lgo-hd-by-person 10-digit');

// 3. GET with params
$getUrl = 'https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person?refDocNo=LGO-HD69-M11&vendorId=10989';
echo "=== GET adapter/export-excel-lgo-hd-by-person ===\n";
$getRes = Http::withHeaders($headers)->withoutVerifying()->get($getUrl);
echo "Status: " . $getRes->status() . "\n";
echo "Length: " . strlen($getRes->body()) . "\n";
echo "Body: " . substr($getRes->body(), 0, 300) . "\n\n";

$getUrl2 = 'https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person?refDocNo=LGO-HD69-M11&vendorId=0000010989';
echo "=== GET adapter/export-excel-lgo-hd-by-person 10-digit ===\n";
$getRes2 = Http::withHeaders($headers)->withoutVerifying()->get($getUrl2);
echo "Status: " . $getRes2->status() . "\n";
echo "Length: " . strlen($getRes2->body()) . "\n";
echo "Body: " . substr($getRes2->body(), 0, 300) . "\n\n";
