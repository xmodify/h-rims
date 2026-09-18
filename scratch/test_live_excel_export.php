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
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $cookieString, $m);
$bearerToken = $m[1] ?? null;

echo "User: " . ($user->name ?? 'Unknown') . "\n";
echo "Token: " . substr($bearerToken, 0, 30) . "...\n\n";

// =========================================================================
// TEST 1: DCKD6931080031 -> POST /dmis/export/dmis
// =========================================================================
echo "=== TEST 1: DCKD6931080031 (/dmis/export/dmis) ===\n";
$dmisPayload = [
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '0000010989',
    'postingDate' => '25690915',
    'sfundCd' => 13,
    'efundCd' => 1,
    'batchNo' => '3270',
    'mophId' => '1102050101.216/217',
];

$res1 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/dmis/export/dmis', $dmisPayload);

echo "Status: " . $res1->status() . "\n";
echo "Content-Type: " . $res1->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res1->header('Content-Disposition') . "\n";
$body1 = $res1->body();
echo "Size: " . strlen($body1) . " bytes\n";
if (strlen($body1) > 0) {
    if (str_starts_with($body1, "PK")) {
        echo ">>> SUCCESS! Valid XLSX file detected!\n";
        // Parse in memory
        $tmp = tempnam(sys_get_temp_dir(), 'dmis_');
        file_put_contents($tmp, $body1);
        try {
            $spreadsheet = IOFactory::load($tmp);
            @unlink($tmp);
            $sheet = $spreadsheet->getActiveSheet();
            echo "Sheet: " . $sheet->getTitle() . ", Rows: " . $sheet->getHighestRow() . ", Cols: " . $sheet->getHighestColumn() . "\n";
            for ($r = 1; $r <= min(6, $sheet->getHighestRow()); $r++) {
                $rowVals = [];
                for ($c = 'A'; $c <= min('M', $sheet->getHighestColumn()); $c++) {
                    $v = $sheet->getCell($c . $r)->getValue();
                    if ($v !== null && $v !== '') $rowVals[] = "{$c}: " . trim($v);
                }
                echo "Row {$r}: " . implode(' | ', $rowVals) . "\n";
            }
        } catch (\Exception $e) {
            @unlink($tmp);
            echo "Parse error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Response: " . substr($body1, 0, 500) . "\n";
    }
}

// =========================================================================
// TEST 2: LGO-HD69-M11 -> POST /adapter/export-excel-lgo-hd-by-person
// =========================================================================
echo "\n=== TEST 2: LGO-HD69-M11 (/adapter/export-excel-lgo-hd-by-person) ===\n";
$lgohdPayload = [
    'refDocNo' => 'LGO-HD69-M11',
    'vendorId' => '0000010989',
    'recId' => 72668,
    'mophId' => '1102050102.801/802',
];

$res2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $bearerToken,
    'Content-Type' => 'application/json',
    'Accept' => 'application/json, text/plain, */*',
    'Origin' => 'https://smt.nhso.go.th',
    'Referer' => 'https://smt.nhso.go.th/smtf/',
])->withoutVerifying()->timeout(30)->post('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person', $lgohdPayload);

echo "Status: " . $res2->status() . "\n";
echo "Content-Type: " . $res2->header('Content-Type') . "\n";
echo "Content-Disposition: " . $res2->header('Content-Disposition') . "\n";
$body2 = $res2->body();
echo "Size: " . strlen($body2) . " bytes\n";
if (strlen($body2) > 0) {
    if (str_starts_with($body2, "PK")) {
        echo ">>> SUCCESS! Valid XLSX file detected!\n";
        $tmp = tempnam(sys_get_temp_dir(), 'lgohd_');
        file_put_contents($tmp, $body2);
        try {
            $spreadsheet = IOFactory::load($tmp);
            @unlink($tmp);
            $sheet = $spreadsheet->getActiveSheet();
            echo "Sheet: " . $sheet->getTitle() . ", Rows: " . $sheet->getHighestRow() . ", Cols: " . $sheet->getHighestColumn() . "\n";
            for ($r = 1; $r <= min(6, $sheet->getHighestRow()); $r++) {
                $rowVals = [];
                for ($c = 'A'; $c <= min('M', $sheet->getHighestColumn()); $c++) {
                    $v = $sheet->getCell($c . $r)->getValue();
                    if ($v !== null && $v !== '') $rowVals[] = "{$c}: " . trim($v);
                }
                echo "Row {$r}: " . implode(' | ', $rowVals) . "\n";
            }
        } catch (\Exception $e) {
            @unlink($tmp);
            echo "Parse error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Response: " . substr($body2, 0, 500) . "\n";
    }
}
