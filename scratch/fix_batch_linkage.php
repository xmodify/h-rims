<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

echo "=== FIXING BATCH LINKAGE FOR DETAILS ===\n";

// Batch 3271 has round 6908_IP_02
SmartMoneyDetail::where('round_no', '6908_IP_02')->update(['batch_no' => '3271']);

// Batch 3034 has round 6908_OP_01
SmartMoneyDetail::where('round_no', '6908_OP_01')->update(['batch_no' => '3034']);

$b3271 = SmartMoneyDetail::where('batch_no', '3271')->count();
$b3034 = SmartMoneyDetail::where('batch_no', '3034')->count();

echo "Batch 3271 (6908_IP_02) count: {$b3271}\n";
echo "Batch 3034 (6908_OP_01) count: {$b3034}\n";
