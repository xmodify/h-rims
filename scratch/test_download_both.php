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

$fullname = $user->name ?? 'นายศิริฤกษ์ คณาดี';

echo "=== TEST 1: LGO-HD69-M11 on sev_other ===\n";
$url1 = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=LGO-HD69-M11&org_type=H&user=" . urlencode($fullname);
$res1 = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(20)->get($url1);

echo "Status: " . $res1->status() . "\n";
echo "Content-Type: " . $res1->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res1->header('Content-Disposition') . "\n";
echo "Length: " . strlen($res1->body()) . " bytes\n";
if (strlen($res1->body()) < 1000) {
    echo "Body preview: " . substr($res1->body(), 0, 500) . "\n";
} else {
    echo "First 50 bytes (hex): " . bin2hex(substr($res1->body(), 0, 50)) . "\n";
    // Check if zip / xlsx (PK header) or HTML or binary
    if (str_starts_with($res1->body(), "PK")) {
        echo ">>> SUCCESS! Valid ZIP/XLSX file format detected!\n";
    } elseif (str_starts_with($res1->body(), "\xD0\xCF\x11\xE0")) {
        echo ">>> SUCCESS! Valid OLE2 (XLS) file format detected!\n";
    }
}

echo "\n=== TEST 2: DCKD6931080031 on sev_other ===\n";
$url2 = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=DCKD6931080031&org_type=H&user=" . urlencode($fullname);
$res2 = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(20)->get($url2);

echo "Status: " . $res2->status() . "\n";
echo "Content-Type: " . $res2->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res2->header('Content-Disposition') . "\n";
echo "Length: " . strlen($res2->body()) . " bytes\n";
if (strlen($res2->body()) < 1000) {
    echo "Body preview: " . substr($res2->body(), 0, 500) . "\n";
} else {
    if (str_starts_with($res2->body(), "PK")) {
        echo ">>> SUCCESS! Valid ZIP/XLSX file format detected!\n";
    } elseif (str_starts_with($res2->body(), "\xD0\xCF\x11\xE0")) {
        echo ">>> SUCCESS! Valid OLE2 (XLS) file format detected!\n";
    }
}
