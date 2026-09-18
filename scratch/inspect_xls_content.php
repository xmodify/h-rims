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
$fullname = $user->name ?? 'นายศิริฤกษ์ คณาดี';

function inspectExcelStream($roundNo, $cookieString, $fullname) {
    echo "\n============================================\n";
    echo "Inspecting Round: {$roundNo}\n";
    echo "============================================\n";
    
    $url = "https://eclaim.nhso.go.th/webComponent/sev_other/DownloadExcel.do?service_token=OTHER&HCODE=10989&STMT_PERIOD=" . urlencode($roundNo) . "&org_type=H&user=" . urlencode($fullname);
    
    $res = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
        'Cookie' => $cookieString,
    ])->withoutVerifying()->timeout(20)->get($url);
    
    $raw = $res->body();
    echo "Downloaded " . strlen($raw) . " bytes\n";
    echo "Filename: " . $res->header('Content-Disposition') . "\n";
    
    // Write to php://memory or temp stream to parse with PhpSpreadsheet without saving on disk
    $tempStream = fopen('php://memory', 'r+');
    fwrite($tempStream, $raw);
    rewind($tempStream);
    
    // We can save to a temporary scratch file just for reader or load from stream
    $tempFile = tempnam(sys_get_temp_dir(), 'test_xls_');
    file_put_contents($tempFile, $raw);
    
    try {
        $spreadsheet = IOFactory::load($tempFile);
        @unlink($tempFile);
        
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        echo "Sheet Title: " . $sheet->getTitle() . "\n";
        echo "Rows: {$highestRow}, Columns: {$highestColumn}\n";
        
        for ($r = 1; $r <= min(15, $highestRow); $r++) {
            $rowVals = [];
            for ($c = 'A'; $c <= min('L', $highestColumn); $c++) {
                $v = $sheet->getCell($c . $r)->getValue();
                if ($v !== null && $v !== '') {
                    $rowVals[] = "{$c}{$r}: " . trim($v);
                }
            }
            if (!empty($rowVals)) {
                echo "Row {$r}: " . implode(' | ', $rowVals) . "\n";
            }
        }
    } catch (\Exception $e) {
        @unlink($tempFile);
        echo "Spreadsheet Error: " . $e->getMessage() . "\n";
    }
}

inspectExcelStream('LGO-HD69-M11', $cookieString, $fullname);
inspectExcelStream('DCKD6931080031', $cookieString, $fullname);
