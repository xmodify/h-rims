<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== CHECK STM TABLES ===\n";
$stmTables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_seamless_dmis',
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

foreach ($stmTables as $t) {
    if (Schema::hasTable($t)) {
        $hasRound = Schema::hasColumn($t, 'round_no');
        $hasRec = Schema::hasColumn($t, 'receive_no');
        $count = DB::table($t)->count();
        $withRec = $hasRec ? DB::table($t)->whereNotNull('receive_no')->where('receive_no', '<>', '')->count() : 0;
        $sampleRounds = $hasRound ? DB::table($t)->whereNotNull('round_no')->where('round_no', '<>', '')->distinct()->limit(3)->pluck('round_no')->toArray() : [];
        echo sprintf("%-20s | Exists: YES | RoundCol: %s | RecCol: %s | Rows: %6d | WithReceipt: %6d | SampleRounds: %s\n",
            $t,
            $hasRound ? 'Y' : 'N',
            $hasRec ? 'Y' : 'N',
            $count,
            $withRec,
            implode(',', $sampleRounds)
        );
    } else {
        echo sprintf("%-20s | Exists: NO\n", $t);
    }
}

echo "\n=== CHECK SMART MONEY BATCHES ===\n";
$batches = DB::table('smart_money_batches')->get();
echo "Total Batches: " . $batches->count() . "\n";
foreach ($batches->take(5) as $b) {
    echo "ID: {$b->id}, Batch: {$b->batch_no}, Round: {$b->round_no}, Account: {$b->account_code}, Amount: {$b->amount}, Receipt: {$b->receive_no}\n";
}

echo "\n=== CHECK SMART MONEY DETAILS ===\n";
$details = DB::table('smart_money_details')->count();
echo "Total Details: {$details}\n";
