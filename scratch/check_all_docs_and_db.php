<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;
use Illuminate\Support\Facades\DB;

echo "=== 1. FILES IN docs/ ===\n";
$files = glob(base_path('docs/*.*'));
foreach ($files as $f) {
    echo "- " . basename($f) . " (" . round(filesize($f)/1024, 2) . " KB)\n";
}

echo "\n=== 2. CURRENT SMART MONEY BATCHES ===\n";
$batches = SmartMoneyBatch::all();
echo "Total Batches in DB: " . $batches->count() . "\n";
foreach ($batches as $b) {
    $detailCount = SmartMoneyDetail::where('batch_no', $b->batch_no)->orWhere('round_no', $b->round_no)->count();
    echo "ID: {$b->id} | Batch: {$b->batch_no} | Date: {$b->transfer_date} | Round: {$b->round_no} | Net: " . number_format($b->net_amount, 2) . " | Receipt: " . ($b->receive_no ?: '-') . " | Details: {$detailCount} rows\n";
}

echo "\n=== 3. CURRENT SMART MONEY DETAILS ===\n";
$totalDetails = SmartMoneyDetail::count();
echo "Total Details in DB: " . $totalDetails . "\n";
echo "Distinct Batches in Details: " . implode(', ', SmartMoneyDetail::distinct()->pluck('batch_no')->filter()->toArray()) . "\n";
echo "Distinct Rounds in Details: " . implode(', ', SmartMoneyDetail::distinct()->pluck('round_no')->filter()->toArray()) . "\n";
