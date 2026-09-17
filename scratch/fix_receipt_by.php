<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use Illuminate\Support\Facades\DB;

// Fix records where receive_no is empty
$updated = SmartMoneyBatch::where(function($q) {
    $q->whereNull('receive_no')->orWhere('receive_no', '');
})->update([
    'receipt_by' => null,
    'receipt_date' => null
]);

echo "Updated $updated records with null receipt_by/receipt_date.\n";

$batches = SmartMoneyBatch::all();
echo "Current batches count: " . $batches->count() . "\n";
foreach ($batches as $b) {
    echo "ID: {$b->id} | Batch: {$b->batch_no} | Round: {$b->round_no} | Account: {$b->account_code} | Receive: '{$b->receive_no}' | Date: '{$b->receipt_date}' | By: '{$b->receipt_by}'\n";
}
