<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batches = App\Models\SmartMoneyBatch::select('id','batch_no','round_no','transfer_date','budget_year')->orderByDesc('transfer_date')->limit(10)->get();
foreach ($batches as $b) {
    echo "Batch: {$b->batch_no} | Round: {$b->round_no} | Date: {$b->transfer_date} | Year: {$b->budget_year}\n";
}
