<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\SmartMoneyBatch;

$nhsoStmTables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_lgo',
    'stm_lgo_kidney',
    'stm_ofc',
    'stm_ofc_csop',
    'stm_ofc_cipn',
    'stm_bkk',
    'stm_bkk_kidney',
    'stm_bmt',
    'stm_bmt_kidney',
    'stm_srt',
    'stm_pvt',
    'stm_sss_kidney',
];

echo "=== TESTING SYNC RECEIPTS FROM STM TO SMART MONEY ===\n";

$matchedRounds = [];
foreach ($nhsoStmTables as $table) {
    if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'round_no') || !Schema::hasColumn($table, 'receive_no')) {
        continue;
    }

    $stmReceipts = DB::table($table)
        ->whereNotNull('receive_no')
        ->where('receive_no', '<>', '')
        ->whereNotNull('round_no')
        ->where('round_no', '<>', '')
        ->select('round_no', 'receive_no', 'receipt_date', 'receipt_by', DB::raw('count(*) as c'))
        ->groupBy('round_no', 'receive_no', 'receipt_date', 'receipt_by')
        ->get();

    foreach ($stmReceipts as $rec) {
        $rNo = trim($rec->round_no);
        $existsInSm = SmartMoneyBatch::where('round_no', $rNo)->count();
        $matchedRounds[] = [
            'table' => $table,
            'round_no' => $rNo,
            'receive_no' => $rec->receive_no,
            'receipt_date' => $rec->receipt_date,
            'receipt_by' => $rec->receipt_by,
            'stm_rows' => $rec->c,
            'sm_batches_count' => $existsInSm,
        ];
    }
}

echo "Total STM Distinct Receipt-Rounds found: " . count($matchedRounds) . "\n";
foreach (array_slice($matchedRounds, 0, 10) as $m) {
    echo sprintf("Table: %-15s | Round: %-25s | Rec: %-10s | By: %-20s | In SM: %d\n", $m['table'], $m['round_no'], $m['receive_no'], $m['receipt_by'], $m['sm_batches_count']);
}
