<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

$user = User::find(3);
$cookieString = $user->eclaim_session_token;

if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m)) {
    $jwt = $m[1];
} else {
    $jwt = $cookieString;
}

$headers = [
    'Authorization' => 'Bearer ' . $jwt,
    'Accept' => 'application/json, text/plain, */*',
    'Content-Type' => 'application/json',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
];

echo "Testing POST /dmis/export/dmis with timeout 60s...\n";
$start = microtime(true);
try {
    $res = Http::withoutVerifying()->withHeaders($headers)->timeout(60)->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', [
        'refDocNo' => 'DCKD6931080031',
        'vendorId' => '10989',
        'postingDate' => '25690915',
        'sfundCd' => 13,
        'efundCd' => 1,
        'batchNo' => '3270',
        'mophId' => '1102050101.216/217',
    ]);
    $dur = round(microtime(true) - $start, 2);
    echo "Status: " . $res->status() . " (in {$dur}s)\n";
    echo "Size: " . strlen($res->body()) . " bytes\n";
    if ($res->status() == 200 && str_starts_with($res->body(), "PK")) {
        echo "🎉 SUCCESS! Received valid XLSX!\n";
        $tmp = tempnam(sys_get_temp_dir(), 'dckd_');
        file_put_contents($tmp, $res->body());
        $spreadsheet = IOFactory::load($tmp);
        @unlink($tmp);
        $sheet = $spreadsheet->getActiveSheet();
        echo "Sheet: " . $sheet->getTitle() . ", Rows: " . $sheet->getHighestRow() . "\n";
    } else {
        echo "Response: " . substr($res->body(), 0, 300) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
