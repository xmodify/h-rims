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

// Test empty body on several person endpoints
$endpoints = [
    '/adapter/report/person/dmis',
    '/dmis/export/dmis',
    '/adapter/report/person/lgohd',
    '/lgohd/export/lgohd',
    '/adapter/report/person/ucep',
    '/adapter/report/person/eclaim',
    '/adapter/report/person/subsidy',
];

foreach ($endpoints as $ep) {
    $res = Http::withHeaders($headers)->withoutVerifying()->post('https://smt.nhso.go.th/smtf/api' . $ep, []);
    echo "$ep => Status: " . $res->status() . ", Length: " . strlen($res->body()) . ", Body: " . substr($res->body(), 0, 100) . "\n";
}
