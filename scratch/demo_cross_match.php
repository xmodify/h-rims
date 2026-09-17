<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Demonstrate how seq_no matches with HOSxP VN / STM / REP
echo "=== DEMONSTRATION: MATCHING SEQ_NO WITH STM_UCS & REP_UCS ===\n";

$sampleSeq = '690805000080';
$sampleHn = '500014963';
$sampleVstdate = '2026-08-05';
$sampleRepno = '690800037';

$stm = DB::table('stm_ucs')
    ->where('hn', $sampleHn)
    ->where('vstdate', $sampleVstdate)
    ->where('repno', $sampleRepno)
    ->first();

if ($stm) {
    echo "Found in stm_ucs:\n";
    echo " - ID: {$stm->id}\n";
    echo " - Round: {$stm->round_no}\n";
    echo " - Tran ID: {$stm->tran_id}\n";
    echo " - Patient: {$stm->pt_name} (HN: {$stm->hn}, CID: {$stm->cid})\n";
    echo " - Receive Total in STM: {$stm->receive_total}\n";
    echo " - Current Receipt No in STM: " . ($stm->receive_no ?: 'None') . "\n";
}

$rep = DB::table('rep_ucs')
    ->where('hn', $sampleHn)
    ->where('repno', $sampleRepno)
    ->first();

if ($rep) {
    echo "\nFound in rep_ucs:\n";
    echo " - Rep ID: {$rep->id}\n";
    echo " - SEQ_NO in rep_ucs: " . ($rep->seq_no ?? 'N/A') . "\n";
    echo " - Tran ID: {$rep->tran_id}\n";
} else {
    echo "\nrep_ucs record for this repno not yet imported or in different round.\n";
}
