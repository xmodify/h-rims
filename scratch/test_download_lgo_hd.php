<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

$urlOther = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=LGO-HD69-M10&org_type=H&user=" . urlencode($user->name);
echo "Testing download OTHER for LGO-HD69-M10: {$urlOther}\n";

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(15)->get($urlOther);

echo "Status: " . $res->status() . "\n";
echo "Content-Type: " . $res->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res->header('Content-Disposition') . "\n";
echo "Length: " . strlen($res->body()) . " bytes\n";
