<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Support\Facades\Http;

$u = App\Models\User::find(3);
auth()->login($u);

$controller = app()->make(SmartMoneyController::class);
$ref = new ReflectionMethod($controller, 'getActiveSmartMoneyToken');
$ref->setAccessible(true);
$tok = $ref->invoke($controller);

echo "Token expires: " . date('Y-m-d H:i:s', 1789780687) . "\n";

$headers = [
    'Authorization' => 'Bearer ' . $tok,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

// Test 1: DMIS report
$res1 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
]);
echo "Test 1 /adapter/report/person/dmis: Status " . $res1->status() . ", Len: " . strlen($res1->body()) . "\n";
if ($res1->status() == 200) {
    echo "Sample: " . substr($res1->body(), 0, 200) . "\n";
}

// Test 2: DMIS export Excel
$res2 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
    'mophId' => '1102050101.216/217'
]);
echo "Test 2 /dmis/export/dmis: Status " . $res2->status() . ", Len: " . strlen($res2->body()) . "\n";
if ($res2->status() == 200) {
    echo "Is XLSX: " . (str_starts_with($res2->body(), 'PK') ? 'YES' : 'NO') . "\n";
}

// Test 3: LGO-HD report
$res3 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
    'recId' => '72668',
]);
echo "Test 3 /adapter/report/person/lgohd: Status " . $res3->status() . ", Len: " . strlen($res3->body()) . "\n";

// Test 4: LGO-HD export Excel
$res4 = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person', [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '10989',
    'recId' => '72668',
    'mophId' => '1102050102.801/802'
]);
echo "Test 4 /adapter/export-excel-lgo-hd-by-person: Status " . $res4->status() . ", Len: " . strlen($res4->body()) . "\n";
