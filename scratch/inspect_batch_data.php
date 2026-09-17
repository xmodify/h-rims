<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batch = App\Models\SmartMoneyBatch::where('batch_no', '2563')->get();
echo "Found " . $batch->count() . " rows for Batch 2563:\n";
foreach ($batch as $b) {
    echo "Round: {$b->round_no} | Acc: {$b->account_code} | FundSub: {$b->fund_sub} | Amount: {$b->amount} | Hold: {$b->hold_amount} | Deduct: {$b->deduct_amount} | Net: {$b->net_amount}\n";
}

$multiBatches = App\Models\SmartMoneyBatch::select('batch_no', DB::raw('count(*) as c'))
    ->groupBy('batch_no')
    ->having('c', '>', 1)
    ->limit(5)
    ->get();

echo "\nOther multi-row batches:\n";
foreach ($multiBatches as $mb) {
    echo "Batch: {$mb->batch_no} ({$mb->c} rows)\n";
    $rows = App\Models\SmartMoneyBatch::where('batch_no', $mb->batch_no)->get();
    foreach ($rows as $r) {
        echo "  - Round: {$r->round_no} | Acc: {$r->account_code} | Amount: {$r->amount} | Hold: {$r->hold_amount} | Deduct: {$r->deduct_amount} | Net: {$r->net_amount}\n";
    }
}
