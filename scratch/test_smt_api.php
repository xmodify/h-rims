<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

// Extract Bearer ACCESS_TOKEN if available
$bearerToken = null;
if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m)) {
    $bearerToken = $m[1];
}

echo "Testing connection to smt.nhso.go.th...\n";
echo "Bearer Token present: " . ($bearerToken ? 'YES' : 'NO') . "\n";

// Test 1: Fetch root / homepage of SMTF to see how it loads scripts
$headers = [
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Accept' => 'application/json, text/plain, */*',
    'Cookie' => $cookieString,
];

if ($bearerToken) {
    $headers['Authorization'] = 'Bearer ' . $bearerToken;
}

try {
    $res = Http::withHeaders($headers)->withoutVerifying()->timeout(10)->get('https://smt.nhso.go.th/smtf/');
    echo "SMTF Root Status: " . $res->status() . "\n";
    echo "SMTF Body snippet: " . substr($res->body(), 0, 500) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
