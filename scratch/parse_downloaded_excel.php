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

$urlOther = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=6908_OP_01&org_type=H&user=" . urlencode($user->name);
$res = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Cookie' => $cookieString,
])->withoutVerifying()->timeout(15)->get($urlOther);

$tmpFile = storage_path('app/temp_6908_OP_01.xls');
file_put_contents($tmpFile, $res->body());

$spreadsheet = IOFactory::load($tmpFile);
$sheet = $spreadsheet->getActiveSheet();
echo "Sheet Title: " . $sheet->getTitle() . "\n";
echo "Highest Row: " . $sheet->getHighestRow() . "\n";

for ($i = 1; $i <= min(15, $sheet->getHighestRow()); $i++) {
    $row = $sheet->rangeToArray("A{$i}:N{$i}")[0];
    $clean = array_values(array_filter($row, fn($v) => $v !== null && $v !== ''));
    if (!empty($clean)) {
        echo "Row $i: " . json_encode($clean, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

unlink($tmpFile);
