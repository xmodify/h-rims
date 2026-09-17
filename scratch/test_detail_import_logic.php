<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

$files = glob(base_path('docs/*6908*.xlsx'));
foreach ($files as $file) {
    echo "========================================\n";
    echo "Testing detail file: " . basename($file) . "\n";

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // 1. Extract metadata from top rows (Row 0 to 6)
    $fileRoundNo = null;
    $fileAccountCode = null;
    foreach (array_slice($rows, 0, 8) as $r) {
        $rowStr = implode(' ', array_filter($r, fn($v) => $v !== null && $v !== ''));
        if (preg_match('/งวด\s*:\s*([^\s]+)/u', $rowStr, $m)) {
            $fileRoundNo = trim($m[1]);
        }
        if (preg_match('/รหัสผังบัญชี[^\:]*:\s*([^\s]+)/u', $rowStr, $m)) {
            $fileAccountCode = trim($m[1]);
        }
    }

    echo "Extracted -> Round No: {$fileRoundNo}, Account Code: {$fileAccountCode}\n";

    // 2. Find matching Batch in smart_money_batches
    $batch = null;
    if ($fileRoundNo && $fileAccountCode) {
        $batch = SmartMoneyBatch::where('round_no', $fileRoundNo)
            ->where('account_code', $fileAccountCode)
            ->first();
    }
    if (!$batch && $fileRoundNo) {
        $batch = SmartMoneyBatch::where('round_no', $fileRoundNo)->first();
    }

    if ($batch) {
        echo "Found Matching Main Batch! -> ID: {$batch->id}, Batch No: {$batch->batch_no}, Round: {$batch->round_no}, Transfer Date: {$batch->transfer_date}\n";
    } else {
        echo "No matching batch found in DB yet.\n";
    }
}
