<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Check Smart Money Batches ===\n";
$smWithReceipt = App\Models\SmartMoneyBatch::whereNotNull('receive_no')->where('receive_no', '!=', '')->get();
echo "SmartMoney rows with receipt: " . $smWithReceipt->count() . "\n";
foreach ($smWithReceipt->take(10) as $r) {
    echo "  - Batch: {$r->batch_no} | Round: '{$r->round_no}' | ReceiveNo: '{$r->receive_no}'\n";
}

$smWithoutReceipt = App\Models\SmartMoneyBatch::where(function($q) {
    $q->whereNull('receive_no')->orWhere('receive_no', '');
})->get();
echo "\nSmartMoney rows WITHOUT receipt: " . $smWithoutReceipt->count() . "\n";
foreach ($smWithoutReceipt->take(5) as $r) {
    echo "  - Batch: {$r->batch_no} | Round: '{$r->round_no}'\n";
}

echo "\n=== Check STM Tables for matching round_no ===\n";
$stmTables = [
    'stm_ucs', 'stm_ucs_kidney', 'stm_seamless_dmis', 'stm_ofc', 'stm_ofc_csop', 
    'stm_ofc_cipn', 'stm_bkk', 'stm_bkk_kidney', 'stm_bmt', 'stm_bmt_kidney', 
    'stm_srt', 'stm_pvt', 'stm_lgo', 'stm_lgo_kidney', 'stm_sss_kidney'
];

foreach ($stmTables as $tbl) {
    if (!Schema::hasTable($tbl)) continue;
    $count = DB::table($tbl)->count();
    $withRec = Schema::hasColumn($tbl, 'receive_no') ? DB::table($tbl)->whereNotNull('receive_no')->where('receive_no', '!=', '')->count() : 0;
    $sample = DB::table($tbl)->whereNotNull('round_no')->limit(3)->pluck('round_no')->toArray();
    echo "Table {$tbl}: Total {$count} rows | with receipt: {$withRec} | sample round_no: " . implode(', ', $sample) . "\n";
}
