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

$batchList = [
    ['b' => 3292, 'p' => '25690918'],
    ['b' => 3289, 'p' => '25690917'],
    ['b' => 3286, 'p' => '25690917'],
];

foreach ($batchList as $item) {
    $b = $item['b'];
    $p = $item['p'];
    $res = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail', [
        'fiscalYear' => '2569',
        'vendorId' => '0000010989',
        'postingDate' => $p,
        'batchNo' => $b,
        'offset' => 0,
        'count' => 5,
        'isTest' => '',
    ]);

    $datas = $res->json('datas') ?? [];
    foreach ($datas as $row) {
        $ref = $row['refDocNo'] ?? '';
        $sfund = $row['sfundCd'] ?? '';
        $efund = $row['efundCd'] ?? '';
        $pdate = $row['postingDate'] ?? '';
        echo "Testing batch $b | ref $ref | sfund $sfund | efund $efund | pdate $pdate...\n";
        
        $resDmis = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
            'refDocNo' => $ref,
            'vendorId' => '10989',
            'postingDate' => (string)$pdate,
            'sfundCd' => (string)$sfund,
            'efundCd' => (string)$efund,
            'batchNo' => (string)$b,
        ]);
        echo "-> Status: " . $resDmis->status() . ", Length: " . strlen($resDmis->body()) . ", Body: " . substr($resDmis->body(), 0, 100) . "\n";
    }
}
