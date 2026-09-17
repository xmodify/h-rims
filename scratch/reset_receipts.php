<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
SmartMoneyBatch::query()->update([
    'receive_no' => null,
    'receipt_date' => null,
    'receipt_by' => null,
]);
echo "Reset all batches to pending receipt. Count: " . SmartMoneyBatch::count() . "\n";
