<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$stmTables = [
    'stm_ucs', 'stm_ucs_kidney', 'stm_seamless_dmis', 'stm_ofc', 'stm_ofc_csop', 
    'stm_ofc_cipn', 'stm_bkk', 'stm_bkk_kidney', 'stm_bmt', 'stm_bmt_kidney', 
    'stm_srt', 'stm_pvt', 'stm_lgo', 'stm_lgo_kidney', 'stm_sss_kidney'
];

$noRec = App\Models\SmartMoneyBatch::whereNull('receive_no')->orWhere('receive_no', '')->get();
echo "SmartMoney rows without receipt: " . $noRec->count() . "\n";

$foundInStm = 0;
foreach ($noRec as $b) {
    if (empty($b->round_no)) continue;
    foreach ($stmTables as $tbl) {
        if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'round_no') || !Schema::hasColumn($tbl, 'receive_no')) continue;
        
        $row = DB::table($tbl)->where('round_no', $b->round_no)->whereNotNull('receive_no')->where('receive_no', '!=', '')->first();
        if ($row) {
            echo "Found match! Batch {$b->batch_no} | Round {$b->round_no} in table {$tbl} with receive_no: {$row->receive_no}\n";
            $foundInStm++;
            break;
        }
    }
}
echo "Total found in STM with receipt: {$foundInStm}\n";

echo "\n=== Now let's test Direction 1 (Smart Money -> STM) ===\n";
$withRec = App\Models\SmartMoneyBatch::whereNotNull('receive_no')->where('receive_no', '!=', '')->get();
echo "SmartMoney rows WITH receipt: " . $withRec->count() . "\n";

$updatedStmCount = 0;
foreach ($withRec as $b) {
    if (empty($b->round_no)) continue;
    foreach ($stmTables as $tbl) {
        if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'round_no') || !Schema::hasColumn($tbl, 'receive_no')) continue;
        
        $stmRows = DB::table($tbl)->where('round_no', $b->round_no)->get();
        if ($stmRows->count() > 0) {
            $alreadySame = DB::table($tbl)->where('round_no', $b->round_no)->where('receive_no', $b->receive_no)->count();
            echo "Batch {$b->batch_no} | Round {$b->round_no} (Rec: {$b->receive_no}) matches {$tbl} ({$stmRows->count()} rows, already same receipt: {$alreadySame})\n";
        }
    }
}
