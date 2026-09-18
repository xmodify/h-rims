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

// =========================================================================
// TEST 1: DMIS person JSON: /adapter/report/person/dmis
// =========================================================================
echo "=== TEST 1: /adapter/report/person/dmis (DCKD6931080031) ===\n";
$dmisPayload = [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '0000010989',
    'postingDate' => '25690915',
    'sfundCd' => 13,
    'efundCd' => 1,
    'batchNo' => '3270',
];

$res1 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', $dmisPayload);

echo "Status: " . $res1->status() . "\n";
$data1 = $res1->json();
if ($res1->status() == 200) {
    echo "vendorName: " . ($data1['vendorName'] ?? '') . "\n";
    echo "vendorCode: " . ($data1['vendorCode'] ?? '') . "\n";
    echo "items count: " . count($data1['item'] ?? []) . "\n";
    if (!empty($data1['item'])) {
        echo "Sample first patient record:\n";
        print_r($data1['item'][0]);
    }
} else {
    echo "Error response:\n";
    print_r($data1);
}

// =========================================================================
// TEST 2: LGO-HD person JSON: /adapter/report/person/lgo-hd
// =========================================================================
echo "\n=== TEST 2: /adapter/report/person/lgo-hd (LGO-HD69-M11) ===\n";
$lgohdPayload = [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '0000010989',
    'recId' => 72668,
];

$res2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgo-hd', $lgohdPayload);

echo "Status: " . $res2->status() . "\n";
$data2 = $res2->json();
if ($res2->status() == 200) {
    echo "vendorName: " . ($data2['vendorName'] ?? '') . "\n";
    echo "vendorType: " . ($data2['vendorType'] ?? '') . "\n";
    echo "items count: " . count($data2['item'] ?? []) . "\n";
    if (!empty($data2['item'])) {
        echo "Sample first patient record:\n";
        print_r($data2['item'][0]);
    }
} else {
    echo "Error response:\n";
    print_r($data2);
}
