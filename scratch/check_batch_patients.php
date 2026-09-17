<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

echo "=== SUMMARY OF BATCHES AND DETAILS IN DB ===\n";
$batches = SmartMoneyBatch::all();
foreach ($batches as $b) {
    $cnt = SmartMoneyDetail::where('batch_no', $b->batch_no)->count();
    $receipt = $b->receive_no ?: 'ยังไม่ออก';
    echo sprintf("Batch: %-6s | Round: %-22s | Transfer: %s | Net: %12s | Receipt: %-10s | Patients: %d\n",
        $b->batch_no,
        $b->round_no,
        $b->transfer_date,
        number_format($b->net_amount, 2),
        $receipt,
        $cnt
    );
}
