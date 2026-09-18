<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

echo "Testing direct Excel download from eclaim.nhso.go.th with users.eclaim_session_token...\n";

// Test 1: sev_other for 6908_OP_01
$urlOther = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=6908_OP_01&org_type=H&user=" . urlencode($user->name);
echo "URL 1: {$urlOther}\n";

$res1 = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(15)->get($urlOther);

echo "Status 1: " . $res1->status() . "\n";
echo "Content-Type 1: " . $res1->header('Content-Type') . "\n";
echo "Length 1: " . strlen($res1->body()) . " bytes\n";
echo "Snippet 1: " . substr($res1->body(), 0, 100) . "\n\n";

// Test 2: sev_ucs for 10989_OPUCS256908_01
$urlUcs = "https://eclaim.nhso.go.th/webComponent/sev_ucs/statementUCSDownloadAction.do?service_token=UCS&document_no=10989_OPUCS256908_01&person_type=1&hcode=10989&org_type=H&month=08&hname=&province_name=&user=" . urlencode($user->name);
echo "URL 2: {$urlUcs}\n";

$res2 = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(15)->get($urlUcs);

echo "Status 2: " . $res2->status() . "\n";
echo "Content-Type 2: " . $res2->header('Content-Type') . "\n";
echo "Length 2: " . strlen($res2->body()) . " bytes\n";
