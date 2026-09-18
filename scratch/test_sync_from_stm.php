<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

// Let's test by inserting one batch with round 6908_IP_02 if not exists
$batch = SmartMoneyBatch::where('round_no', '6908_IP_02')->first();
if (!$batch) {
    echo "Creating test batch 3271 for 6908_IP_02...\n";
    $batch = SmartMoneyBatch::create([
        'batch_no' => '3271',
        'round_no' => '6908_IP_02',
        'account_code' => '1102050101.202',
        'fund_main' => 'กองทุนผู้ป่วยใน',
        'fund_sub' => 'กองทุนผู้ป่วยใน CAP',
        'amount' => 1248382.53,
        'net_amount' => 1248382.53,
        'transfer_date' => '2026-09-15',
        'budget_year' => 2569,
    ]);
}

echo "Batch: {$batch->batch_no}, Round: {$batch->round_no}\n";

// Query stm_ucs
$stmRows = DB::table('stm_ucs')->where('round_no', $batch->round_no)->get();
echo "Found " . $stmRows->count() . " rows in stm_ucs for round {$batch->round_no}\n";

$imported = 0;
foreach ($stmRows as $r) {
    SmartMoneyDetail::updateOrInsert(
        [
            'round_no' => $batch->round_no,
            'hn' => $r->hn,
            'an' => !empty($r->an) ? $r->an : null,
            'vstdate' => $r->vstdate,
            'repno' => $r->repno,
        ],
        [
            'batch_no' => $batch->batch_no,
            'transfer_date' => $batch->transfer_date,
            'pt_type' => !empty($r->an) ? 'ผู้ป่วยใน' : 'ผู้ป่วยนอก',
            'cid' => $r->cid,
            'pt_name' => $r->pt_name,
            'receive_total' => (float)$r->receive_total,
            'main_fund' => $batch->fund_main,
            'sub_fund' => $batch->fund_sub,
            'budget_year' => $batch->budget_year,
        ]
    );
    $imported++;
}

echo "Successfully imported/synced {$imported} patient details into smart_money_details!\n";
$totalInDetails = SmartMoneyDetail::where('batch_no', $batch->batch_no)->count();
$sumInDetails = SmartMoneyDetail::where('batch_no', $batch->batch_no)->sum('receive_total');
echo "smart_money_details count for Batch {$batch->batch_no}: {$totalInDetails}, Sum: " . number_format($sumInDetails, 2) . "\n";
