<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

$user = DB::table('users')->where('id', 3)->first();
$cookieString = $user->eclaim_session_token;

$url = "https://eclaim.nhso.go.th/webComponent/sev_ucs/statementUCSDownloadAction.do?service_token=UCS&document_no=10989_IPUCS256908_02&person_type=2&hcode=10989&org_type=H&month=08&hname=&province_name=&user=" . urlencode($user->name);

$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(20)->get($url);

echo "Status: " . $res->status() . ", Size: " . strlen($res->body()) . "\n";

$tmp = storage_path('app/test_ipucs.xls');
file_put_contents($tmp, $res->body());

$spreadsheet = IOFactory::load($tmp);
echo "Sheets: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";

foreach ($spreadsheet->getAllSheets() as $idx => $sh) {
    echo "Sheet $idx: " . $sh->getTitle() . " has " . $sh->getHighestRow() . " rows\n";
}

unlink($tmp);
