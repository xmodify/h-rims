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

// 1. Check pending SmartMoney rounds
$noRecRounds = App\Models\SmartMoneyBatch::where(function($q) {
    $q->whereNull('receive_no')->orWhere('receive_no', '');
})->whereNotNull('round_no')->where('round_no', '!=', '')->pluck('round_no')->unique()->toArray();

echo "Pending SmartMoney unique round_nos: " . count($noRecRounds) . "\n";

$foundInStm = 0;
foreach ($stmTables as $tbl) {
    if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'round_no') || !Schema::hasColumn($tbl, 'receive_no')) continue;
    
    $matches = DB::table($tbl)
        ->whereIn('round_no', $noRecRounds)
        ->whereNotNull('receive_no')
        ->where('receive_no', '!=', '')
        ->select('round_no', 'receive_no', 'receipt_date')
        ->distinct()
        ->get();
        
    if ($matches->count() > 0) {
        echo "Table {$tbl} has " . $matches->count() . " matches with receipts for pending SmartMoney rounds!\n";
        foreach ($matches->take(3) as $m) {
            echo "   -> Round: {$m->round_no} | Receipt: {$m->receive_no}\n";
        }
        $foundInStm += $matches->count();
    }
}

if ($foundInStm === 0) {
    echo "=> Result: ในตาราง STM ทุกสิทธิ์ ยังไม่มีรายการใดที่มีเลขที่ใบเสร็จสำหรับงวดที่ยังค้างอยู่ใน Smart Money (คือใน STM ก็ยังไม่ได้ออกใบเสร็จเช่่นกัน)\n";
}

// 2. Check SmartMoney with Receipts vs STM
$withRecRounds = App\Models\SmartMoneyBatch::whereNotNull('receive_no')
    ->where('receive_no', '!=', '')
    ->whereNotNull('round_no')
    ->where('round_no', '!=', '')
    ->get(['round_no', 'receive_no', 'receipt_date']);

echo "\nSmartMoney rows WITH receipt: " . $withRecRounds->count() . "\n";

$matchingStmRowsTotal = 0;
$alreadyUpToDateStm = 0;
$needsUpdateStm = 0;

$roundMap = [];
foreach ($withRecRounds as $r) {
    $roundMap[$r->round_no] = $r;
}
$roundsList = array_keys($roundMap);

foreach ($stmTables as $tbl) {
    if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'round_no') || !Schema::hasColumn($tbl, 'receive_no')) continue;
    
    $stmRows = DB::table($tbl)->whereIn('round_no', $roundsList)->get(['round_no', 'receive_no']);
    if ($stmRows->count() > 0) {
        $matchingStmRowsTotal += $stmRows->count();
        foreach ($stmRows as $sr) {
            $expected = $roundMap[$sr->round_no]->receive_no ?? '';
            if ($sr->receive_no === $expected) {
                $alreadyUpToDateStm++;
            } else {
                $needsUpdateStm++;
            }
        }
    }
}

echo "Matching rows found in STM tables: {$matchingStmRowsTotal}\n";
echo " - Already have exact same receipt (Up to date 100%): {$alreadyUpToDateStm}\n";
echo " - Needs update (Missing / Different receipt): {$needsUpdateStm}\n";
