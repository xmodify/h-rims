<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tokenData = json_decode(file_get_contents(__DIR__ . '/smt_token.json'), true);
$token = $tokenData['token'];

$postData = [
    'vendorSearchConditionCode' => '1',
    'zoneId' => '',
    'provinceId' => '',
    'vendorId' => '0000010989',
    'vendorId5Digit' => '',
    'budgetSource' => '',
    'budgetYear' => '2569',
    'transferStartDate' => '01/09/2569',
    'transferEndDate' => '18/09/2569',
    'hospType' => '',
    'isTest' => '',
];

$res = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

$json = $res->json();
$datas = $json['datas'] ?? [];
echo "Total datas from SMT: " . count($datas) . "\n";
if (!empty($datas)) {
    // Print all keys of first item
    echo "Keys in data item: " . implode(', ', array_keys($datas[0])) . "\n\n";
    // Find DCKD and LGO items
    foreach ($datas as $d) {
        if (in_array($d['refDocNo'] ?? '', ['LGO-HD69-M11', 'DCKD6931080031'])) {
            echo "Item " . ($d['refDocNo'] ?? '') . ":\n";
            print_r($d);
        }
    }
}
