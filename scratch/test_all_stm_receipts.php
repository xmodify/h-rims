<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\SmartMoneyBatch;

$stmTables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_seamless_dmis',
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

echo "=== CHECKING TOTAL RECEIPTS AVAILABLE IN ALL STM INCLUDING stm_seamless_dmis ===\n";
$allDistinctRounds = [];

foreach ($stmTables as $table) {
    if (Schema::hasTable($table) && Schema::hasColumn($table, 'round_no') && Schema::hasColumn($table, 'receive_no')) {
        $recs = DB::table($table)
            ->whereNotNull('receive_no')
            ->where('receive_no', '<>', '')
            ->whereNotNull('round_no')
            ->where('round_no', '<>', '')
            ->select('round_no', 'receive_no', 'receipt_date', 'receipt_by', DB::raw('count(*) as cnt'))
            ->groupBy('round_no', 'receive_no', 'receipt_date', 'receipt_by')
            ->get();
        echo "Table: {$table} => " . $recs->count() . " rounds with receipts (" . $recs->sum('cnt') . " total rows)\n";
        foreach ($recs as $r) {
            $allDistinctRounds[$r->round_no] = [
                'table' => $table,
                'receive_no' => $r->receive_no,
                'receipt_date' => $r->receipt_date,
                'receipt_by' => $r->receipt_by,
                'rows' => $r->cnt
            ];
        }
    }
}

echo "\nTotal unique rounds with receipts across ALL STM tables: " . count($allDistinctRounds) . "\n";
