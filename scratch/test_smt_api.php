<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$tokenData = json_decode(file_get_contents(__DIR__ . '/smt_token.json'), true);
$token = $tokenData['token'];

echo "=== Calling /adapter/report/person/dmis with SMT token ===\n";

$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

// Let's test payload for DCKD6931080031
// Route was: /home/budget/summary-detail-dmis/:refDocNo/:vendorId/:postingDate/:batchNo/:sfundCd/:efundCd
// DCKD6931080031/10989/25690915/3270/13/1?mophId=1102050101.216/217

$payloads = [
    [
        'refDocNo' => 'DCKD6931080031',
        'vendorId' => '10989',
        'postingDate' => '25690915',
        'batchNo' => '3270',
        'sfundCd' => '13',
        'efundCd' => '1',
    ],
    [
        'docNo' => 'DCKD6931080031',
        'hcode' => '10989',
    ]
];

foreach ($payloads as $p) {
    echo "Testing payload: " . json_encode($p) . "\n";
    $res = Http::withoutVerifying()->withHeaders($headers)->post('https://smt.nhso.go.th/adapter/report/person/dmis', $p);
    echo "Status: " . $res->status() . "\n";
    echo "Body: " . substr($res->body(), 0, 300) . "\n\n";
}
