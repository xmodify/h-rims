<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmartMoneyBatch;

$budget_year = 2569;
$rawBatches = SmartMoneyBatch::where('budget_year', $budget_year)
    ->orderByDesc('transfer_date')
    ->orderByDesc('batch_no')
    ->orderBy('id')
    ->get();

$groupedBatches = [];
foreach ($rawBatches as $row) {
    $bNo = $row->batch_no;
    if (!isset($groupedBatches[$bNo])) {
        $groupedBatches[$bNo] = (object)[
            'id' => $row->id,
            'batch_no' => $bNo,
            'transfer_date' => $row->transfer_date,
            'budget_year' => $row->budget_year,
            'file_name' => $row->file_name,
            'receive_no' => $row->receive_no,
            'receipt_date' => $row->receipt_date,
            'receipt_by' => $row->receipt_by,
            'total_amount' => 0,
            'total_hold_amount' => 0,
            'total_deduct_amount' => 0,
            'total_guarantee_amount' => 0,
            'total_tax_amount' => 0,
            'total_remain_amount' => 0,
            'total_offset_amount' => 0,
            'total_net_amount' => 0,
            'round_nos' => [],
            'account_codes' => [],
            'fund_mains' => [],
            'fund_subs' => [],
            'items' => collect([]),
        ];
    }
    $groupedBatches[$bNo]->total_amount += (float)$row->amount;
    $groupedBatches[$bNo]->total_hold_amount += (float)$row->hold_amount;
    $groupedBatches[$bNo]->total_deduct_amount += (float)$row->deduct_amount;
    $groupedBatches[$bNo]->total_guarantee_amount += (float)$row->guarantee_amount;
    $groupedBatches[$bNo]->total_tax_amount += (float)$row->tax_amount;
    $groupedBatches[$bNo]->total_remain_amount += (float)$row->remain_amount;
    $groupedBatches[$bNo]->total_offset_amount += (float)$row->offset_amount;
    $groupedBatches[$bNo]->total_net_amount += (float)$row->net_amount;

    if (!empty($row->round_no) && !in_array($row->round_no, $groupedBatches[$bNo]->round_nos)) {
        $groupedBatches[$bNo]->round_nos[] = $row->round_no;
    }
    if (!empty($row->account_code) && !in_array($row->account_code, $groupedBatches[$bNo]->account_codes)) {
        $groupedBatches[$bNo]->account_codes[] = $row->account_code;
    }
    if (!empty($row->fund_main) && !in_array($row->fund_main, $groupedBatches[$bNo]->fund_mains)) {
        $groupedBatches[$bNo]->fund_mains[] = $row->fund_main;
    }
    if (!empty($row->fund_sub) && !in_array($row->fund_sub, $groupedBatches[$bNo]->fund_subs)) {
        $groupedBatches[$bNo]->fund_subs[] = $row->fund_sub;
    }
    if (!empty($row->receive_no) && empty($groupedBatches[$bNo]->receive_no)) {
        $groupedBatches[$bNo]->receive_no = $row->receive_no;
        $groupedBatches[$bNo]->receipt_date = $row->receipt_date;
        $groupedBatches[$bNo]->receipt_by = $row->receipt_by;
    }
    if (!empty($row->file_name) && empty($groupedBatches[$bNo]->file_name)) {
        $groupedBatches[$bNo]->file_name = $row->file_name;
    }
    $groupedBatches[$bNo]->items->push($row);
}

echo "Total Batches: " . count($groupedBatches) . "\n";
foreach ($groupedBatches as $b) {
    echo "Batch {$b->batch_no} | Items: " . $b->items->count() . " | Net: " . number_format($b->total_net_amount, 2) . " | Rounds: " . implode(', ', $b->round_nos) . " | Accounts: " . implode(', ', $b->account_codes) . "\n";
}
