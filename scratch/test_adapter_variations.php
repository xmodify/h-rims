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

function testPost($url, $payload, $label) {
    global $headers;
    echo "--- Testing: $label ---\n";
    echo "URL: $url\n";
    echo "Payload: " . json_encode($payload) . "\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $httpHeaders = [];
    foreach ($headers as $k => $v) {
        $httpHeaders[] = "$k: $v";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    echo "HTTP Status: " . $info['http_code'] . "\n";
    
    $headerSize = $info['header_size'];
    $headerStr = substr($res, 0, $headerSize);
    $body = substr($res, $headerSize);
    echo "Body length: " . strlen($body) . "\n";
    echo "Body preview: " . substr($body, 0, 500) . "\n\n";
}

// Variations for LGOHD
testPost('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', ['recId' => 72668, 'vendorId' => '10989'], 'LGOHD int recId, 5-digit vendor');
testPost('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', ['recId' => '72668', 'vendorId' => '10989'], 'LGOHD str recId, 5-digit vendor');
testPost('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', ['recId' => 72668, 'vendorId' => '0000010989'], 'LGOHD int recId, 10-digit vendor');
testPost('https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd', ['recId' => 72668, 'vendorId' => '10989', 'mophId' => '1102050102.801/802'], 'LGOHD export excel 5-digit');

// Variations for DMIS
testPost('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270'
], 'DMIS report 5-digit vendor strings');

testPost('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '0000010989',
    'postingDate' => '25690915',
    'sfundCd' => 13,
    'efundCd' => 1,
    'batchNo' => 3270
], 'DMIS report 10-digit vendor ints');

testPost('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
    'mophId' => '1102050101.216/217'
], 'DMIS export excel');
