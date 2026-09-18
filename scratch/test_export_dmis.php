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

echo "Testing /dmis/export/dmis with Bearer token...\n";

// Batch 2812 is DCKD6924070024
$postData = [
    'refDocNo' => 'DCKD6924070024',
    'vendorId' => '10989',
    'postingDate' => '25690814',
    'sfundCd' => 24,
    'efundCd' => 7,
    'batchNo' => '2812',
    'mophId' => '1102050101.216/217',
];

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => '*/*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(20)->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', $postData);

echo "Status: " . $res->status() . "\n";
echo "Content-Type: " . $res->header('Content-Type') . "\n";
echo "Length: " . strlen($res->body()) . " bytes\n";
