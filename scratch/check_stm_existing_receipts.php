<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$stmTables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_ofc',
    'stm_ofc_csop',
    'stm_ofc_cipn',
    'stm_bkk',
    'stm_bkk_kidney',
    'stm_bmt',
    'stm_bmt_kidney',
    'stm_srt',
    'stm_pvt',
    'stm_lgo',
    'stm_lgo_kidney',
    'stm_sss_kidney',
];

echo "=== CHECKING EXISTING RECEIPTS IN STM TABLES ===\n";
$totalReceiptsFound = 0;
foreach ($stmTables as $table) {
    if (Schema::hasTable($table) && Schema::hasColumn($table, 'receive_no')) {
        $count = DB::table($table)->whereNotNull('receive_no')->where('receive_no', '<>', '')->count();
        if ($count > 0) {
            $distinctRounds = DB::table($table)
                ->whereNotNull('receive_no')
                ->where('receive_no', '<>', '')
                ->select('round_no', 'receive_no', 'receipt_date', 'receipt_by', DB::raw('count(*) as row_count'))
                ->groupBy('round_no', 'receive_no', 'receipt_date', 'receipt_by')
                ->get();
            echo "Table {$table}: Found {$count} rows with receipts across " . $distinctRounds->count() . " rounds:\n";
            foreach ($distinctRounds as $dr) {
                echo "   -> Round: '{$dr->round_no}' | RecNo: '{$dr->receive_no}' | Date: '{$dr->receipt_date}' | By: '{$dr->receipt_by}' ({$dr->row_count} rows)\n";
            }
            $totalReceiptsFound += $count;
        }
    }
}

if ($totalReceiptsFound === 0) {
    echo "No receipts currently recorded in STM tables (all currently empty).\n";
}
