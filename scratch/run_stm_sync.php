<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new App\Http\Controllers\SmartMoneyController();
$res = $controller->syncReceiptsFromStm(new Illuminate\Http\Request());
echo "Sync Result: " . $res->getContent() . "\n";

$batches = App\Models\SmartMoneyBatch::all();
foreach ($batches as $b) {
    if (!empty($b->receive_no)) {
        echo "Batch: {$b->batch_no} | Round: {$b->round_no} | Receipt: {$b->receive_no} | Date: {$b->receipt_date} | By: {$b->receipt_by}\n";
    }
}
