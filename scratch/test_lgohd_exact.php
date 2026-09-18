<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$bearerToken = $m[1] ?? null;

echo "=== TEST A: POST /adapter/report/person/lgohd ===\n";
$resA = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/report/person/lgohd', [
    'recId' => 72668,
    'vendorId' => '10989',
]);

echo "Status: " . $resA->status() . "\n";
echo "Body: " . substr($resA->body(), 0, 500) . "\n";

echo "\n=== TEST B: POST /lgohd/export/lgohd ===\n";
$resB = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/lgohd/export/lgohd', [
    'recId' => 72668,
    'vendorId' => '10989',
    'mophId' => '1102050102.801/802',
]);

echo "Status: " . $resB->status() . ", size: " . strlen($resB->body()) . " bytes\n";
if ($resB->status() == 200) {
    echo ">>> SUCCESS! Body start: " . bin2hex(substr($resB->body(), 0, 20)) . "\n";
}
