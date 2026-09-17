<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;

$rawBatches = SmartMoneyBatch::all();
echo "Total raw rows: " . $rawBatches->count() . "\n";

$groupedBatches = [];
foreach ($rawBatches as $row) {
    $bNo = $row->batch_no;
    if (!isset($groupedBatches[$bNo])) {
        $groupedBatches[$bNo] = (object)[
            'batch_no' => $bNo,
            'transfer_date' => $row->transfer_date,
            'budget_year' => $row->budget_year,
            'file_name' => $row->file_name,
            'receive_no' => $row->receive_no,
            'receipt_date' => $row->receipt_date,
            'receipt_by' => $row->receipt_by,
            'total_amount' => 0,
            'total_net_amount' => 0,
            'items' => collect([]),
        ];
    }
    $groupedBatches[$bNo]->total_amount += (float)$row->amount;
    $groupedBatches[$bNo]->total_net_amount += (float)$row->net_amount;
    $groupedBatches[$bNo]->items->push($row);
}

echo "Grouped batches count: " . count($groupedBatches) . "\n";
foreach ($groupedBatches as $bNo => $g) {
    echo "Batch: $bNo | Date: {$g->transfer_date} | Sub-rows: " . $g->items->count() . " | Total Net: " . number_format($g->total_net_amount, 2) . "\n";
    foreach ($g->items as $it) {
        echo "   -> Round: {$it->round_no} | Account: {$it->account_code} | Fund: {$it->fund_main} - {$it->fund_sub} | Net: " . number_format($it->net_amount, 2) . "\n";
    }
}
