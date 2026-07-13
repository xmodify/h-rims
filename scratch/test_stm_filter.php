<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$round = '10703_SOCDSTM_20260501';

// Check if records exist in DB first. If not, let's notify.
$count = DB::table('stm_sss_kidney')->where('round_no', $round)->count();
if ($count == 0) {
    echo "Warning: No records found for round '$round' in DB. Have you imported the ZIP file yet?\n";
} else {
    echo "Found $count records in DB for round '$round'.\n";
    
    // Original style summary (total):
    $orig = DB::table('stm_sss_kidney')
        ->select(DB::raw('count(*) as count_no, sum(amount) as amount, sum(epopay) as epopay, sum(epoadm) as epoadm'))
        ->where('round_no', $round)
        ->first();
    
    $orig_total = $orig->amount + $orig->epopay + $orig->epoadm;
    echo "Original Total (Unfiltered):\n";
    echo "  Count: {$orig->count_no}\n";
    echo "  HD Amt: {$orig->amount}\n";
    echo "  EPOpay: {$orig->epopay}\n";
    echo "  EPOadm: {$orig->epoadm}\n";
    echo "  Grand Total: $orig_total\n";
    
    // New style summary (filtered):
    $filtered = DB::table('stm_sss_kidney')
        ->select(DB::raw('count(*) as count_no, sum(amount) as amount, sum(epopay) as epopay, sum(epoadm) as epoadm'))
        ->where('round_no', $round)
        ->whereColumn('hreg', 'hcode')
        ->first();
        
    $filtered_total = $filtered->amount + $filtered->epopay + $filtered->epoadm;
    echo "New Total (Filtered by hreg = hcode):\n";
    echo "  Count: {$filtered->count_no}\n";
    echo "  HD Amt: {$filtered->amount}\n";
    echo "  EPOpay: {$filtered->epopay}\n";
    echo "  EPOadm: {$filtered->epoadm}\n";
    echo "  Grand Total: $filtered_total\n";
}
