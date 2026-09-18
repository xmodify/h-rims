<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m);
$bearerToken = $m[1] ?? null;

echo "=== TEST 1: GET /adapter/export-excel-lgo-hd-by-person ===\n";
$res1 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
])->withoutVerifying()->timeout(30)->get('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person?refDocNo=LGO-HD69-M11&vendorId=10989');

echo "GET Status: " . $res1->status() . ", size: " . strlen($res1->body()) . " bytes\n";
if ($res1->status() == 200 && str_starts_with($res1->body(), "PK")) {
    echo ">>> SUCCESS on GET! XLSX detected!\n";
}

echo "\n=== TEST 2: POST /adapter/export-excel-lgo-hd-by-person ===\n";
$res2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
    'recId' => 72668,
    'mophId' => '1102050102.801/802',
]);

echo "POST Status: " . $res2->status() . ", size: " . strlen($res2->body()) . " bytes\n";
if ($res2->status() == 200 && str_starts_with($res2->body(), "PK")) {
    echo ">>> SUCCESS on POST! XLSX detected!\n";
}

echo "\n=== TEST 3: POST /adapter/report/person/lgohd (JSON) ===\n";
$res3 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
    'recId' => 72668,
]);

echo "Status: " . $res3->status() . "\n";
if ($res3->status() == 200) {
    echo "vendorName: " . ($res3->json()['vendorName'] ?? '') . "\n";
    echo "Item count: " . count($res3->json()['item'] ?? []) . "\n";
    if (!empty($res3->json()['item'])) {
        echo "First item:\n";
        print_r($res3->json()['item'][0]);
    }
}

echo "\n=== TEST 4: POST /dmis/export/dmis ===\n";
$res4 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => 13,
    'efundCd' => 1,
    'batchNo' => '3270',
    'mophId' => '1102050101.216/217',
]);

echo "Status: " . $res4->status() . ", size: " . strlen($res4->body()) . " bytes\n";
if ($res4->status() == 200 && str_starts_with($res4->body(), "PK")) {
    echo ">>> SUCCESS on DMIS export! XLSX detected!\n";
}

echo "\n=== TEST 5: POST /adapter/report/person/dmis (JSON) ===\n";
$res5 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => 13,
    'efundCd' => 1,
    'batchNo' => '3270',
]);

echo "Status: " . $res5->status() . "\n";
if ($res5->status() == 200) {
    echo "vendorName: " . ($res5->json()['vendorName'] ?? '') . "\n";
    echo "Item count: " . count($res5->json()['item'] ?? []) . "\n";
    if (!empty($res5->json()['item'])) {
        echo "First item:\n";
        print_r($res5->json()['item'][0]);
    }
}
