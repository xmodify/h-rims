<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$jwt = $m[1];

echo "Testing /adapter/report/person/dmis...\n";
$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $jwt,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis', [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270',
]);

echo "Status: " . $res->status() . "\n";
echo "Headers:\n";
print_r($res->headers());
echo "\nBody:\n" . $res->body() . "\n";
