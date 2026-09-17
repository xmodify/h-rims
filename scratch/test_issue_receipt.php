<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;
use App\Models\SmartMoneyBatch;

$controller = new SmartMoneyController();
$batch = SmartMoneyBatch::first();

echo "Testing issuing receipt for Batch {$batch->batch_no}...\n";
$req = new Request([
    'batch_no' => $batch->batch_no,
    'receive_no' => '75/2569',
    'receipt_date' => '2026-09-17',
]);

$res = $controller->updateReceipt($req);
echo "Response: " . $res->getContent() . "\n\n";

$updatedBatches = SmartMoneyBatch::where('batch_no', $batch->batch_no)->get();
foreach ($updatedBatches as $b) {
    echo "ID: {$b->id} | Batch: {$b->batch_no} | Account: {$b->account_code} | Receive: {$b->receive_no} | Date: {$b->receipt_date} | By: {$b->receipt_by}\n";
}
