<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

$url = 'https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person?refDocNo=LGO-HD69-M11&vendorId=10989';

echo "Testing GET with Cookie header...\nURL: $url\n";
$res = Http::withHeaders([
    'Cookie' => $cookieString,
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->get($url);

echo "Status: " . $res->status() . "\n";
echo "Content-Type: " . $res->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res->header('Content-Disposition') . "\n";
echo "Length: " . strlen($res->body()) . "\n";
echo "Body: " . substr($res->body(), 0, 500) . "\n";
