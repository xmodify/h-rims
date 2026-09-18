<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;

$user = User::find(3);
$cookieString = $user->eclaim_session_token;

if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m)) {
    $jwt = $m[1];
} else {
    $jwt = $cookieString;
}

echo "Extracted JWT length: " . strlen($jwt) . "\n";

$headers = [
    'Authorization' => 'Bearer ' . $jwt,
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

// 1. Test DMIS (DCKD6931080031) - JSON
echo "\n============================================\n";
echo "1. Testing DMIS report JSON (/adapter/report/person/dmis)...\n";
$res1 = Http::withoutVerifying()->withHeaders($headers)->timeout(20)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
]);
echo "Status: " . $res1->status() . "\n";
echo "Content-Type: " . $res1->header('Content-Type') . "\n";
$body1 = $res1->body();
echo "Body length: " . strlen($body1) . "\n";
if ($res1->successful()) {
    $json1 = $res1->json();
    echo "Vendor: " . ($json1['vendorName'] ?? 'none') . "\n";
    $items = $json1['item'] ?? $json1['items'] ?? [];
    echo "Item count: " . count($items) . "\n";
    if (!empty($items)) {
        echo "Sample item 0: " . json_encode($items[0], JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "Body: " . substr($body1, 0, 500) . "\n";
}

// 2. Test DMIS (DCKD6931080031) - Excel
echo "\n============================================\n";
echo "2. Testing DMIS Excel export (/dmis/export/dmis)...\n";
$res2 = Http::withoutVerifying()->withHeaders($headers)->timeout(30)->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
    'mophId' => '1102050101.216/217',
]);
echo "Status: " . $res2->status() . "\n";
echo "Content-Type: " . $res2->header('Content-Type') . "\n";
echo "Body length: " . strlen($res2->body()) . "\n";
if ($res2->successful()) {
    echo "First 50 bytes (hex): " . bin2hex(substr($res2->body(), 0, 50)) . "\n";
    echo "Header check PK (zip/xlsx): " . (substr($res2->body(), 0, 2) === "PK" ? "YES, IT IS XLSX!" : "Not PK") . "\n";
} else {
    echo "Error body: " . substr($res2->body(), 0, 500) . "\n";
}

// 3. Test LGOHD (LGO-HD69-M11) - JSON
echo "\n============================================\n";
echo "3. Testing LGOHD report JSON (/adapter/report/person/lgohd)...\n";
$res3 = Http::withoutVerifying()->withHeaders($headers)->timeout(20)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', [
    'recId' => 72668,
    'vendorId' => '10989',
]);
echo "Status: " . $res3->status() . "\n";
echo "Content-Type: " . $res3->header('Content-Type') . "\n";
$body3 = $res3->body();
echo "Body length: " . strlen($body3) . "\n";
if ($res3->successful()) {
    $json3 = $res3->json();
    echo "Vendor: " . ($json3['vendorName'] ?? 'none') . "\n";
    $items = $json3['items'] ?? $json3['item'] ?? [];
    echo "Items count: " . count($items) . "\n";
    if (!empty($items)) {
        echo "Sample item 0: " . json_encode($items[0], JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "Body: " . substr($body3, 0, 500) . "\n";
}

// 4. Test LGOHD (LGO-HD69-M11) - Excel
echo "\n============================================\n";
echo "4. Testing LGOHD Excel export (/lgohd/export/lgohd)...\n";
$res4 = Http::withoutVerifying()->withHeaders($headers)->timeout(30)->post('https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd', [
    'recId' => 72668,
    'vendorId' => '10989',
    'mophId' => '1102050102.801/802',
]);
echo "Status: " . $res4->status() . "\n";
echo "Content-Type: " . $res4->header('Content-Type') . "\n";
echo "Body length: " . strlen($res4->body()) . "\n";
if ($res4->successful()) {
    echo "First 50 bytes (hex): " . bin2hex(substr($res4->body(), 0, 50)) . "\n";
    echo "Header check PK (zip/xlsx): " . (substr($res4->body(), 0, 2) === "PK" ? "YES, IT IS XLSX!" : "Not PK") . "\n";
} else {
    echo "Error body: " . substr($res4->body(), 0, 500) . "\n";
}
