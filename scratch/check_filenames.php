<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$samples = App\Models\SmartMoneyBatch::select('id','batch_no','transfer_date','file_name')->limit(10)->get();
foreach ($samples as $s) {
    echo "Batch: {$s->batch_no} | File: '{$s->file_name}' | Date: {$s->transfer_date}\n";
}
