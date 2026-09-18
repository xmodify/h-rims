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
    'User-Agent' => 'Mozilla/5.0',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

$datas = $res->json()['datas'] ?? [];
echo "Found " . count($datas) . " items in SMT:\n";
foreach ($datas as $d) {
    $b = $d['batchNo'] ?? '-';
    $r = $d['refDocNo'] ?? '-';
    $f = mb_substr($d['fundName'] ?? '-', 0, 25);
    $paym = !empty($d['downloadPAYMFileName']) ? 'YES' : 'NO';
    $link = $d['linkConfigId'] ?? 0;
    $rec = $d['recId'] ?? '-';
    echo sprintf("Batch: %-6s | Round: %-16s | Fund: %-25s | PAYM: %s | link: %s | recId: %s\n", $b, $r, $f, $paym, $link, $rec);
}
