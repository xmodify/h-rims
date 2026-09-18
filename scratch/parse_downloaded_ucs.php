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

$urlUcs = "https://eclaim.nhso.go.th/webComponent/sev_ucs/statementUCSDownloadAction.do?service_token=UCS&document_no=10989_OPUCS256908_01&person_type=1&hcode=10989&org_type=H&month=08&hname=&province_name=&user=" . urlencode($user->name);
$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(20)->get($urlUcs);

$tmpFile = storage_path('app/temp_ucs_op.xls');
file_put_contents($tmpFile, $res->body());

$spreadsheet = IOFactory::load($tmpFile);
echo "Sheet Names: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";
$sheet = $spreadsheet->getSheet(0);
echo "Sheet 0: " . $sheet->getTitle() . ", Highest Row: " . $sheet->getHighestRow() . "\n";

for ($i = 1; $i <= min(10, $sheet->getHighestRow()); $i++) {
    $row = $sheet->rangeToArray("A{$i}:N{$i}")[0];
    $clean = array_values(array_filter($row, fn($v) => $v !== null && $v !== ''));
    if (!empty($clean)) {
        echo "Row $i: " . json_encode($clean, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

unlink($tmpFile);
