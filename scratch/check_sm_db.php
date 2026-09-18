<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

echo "Batches Count: " . SmartMoneyBatch::count() . "\n";
echo "Details Count: " . SmartMoneyDetail::count() . "\n";

$batches = SmartMoneyBatch::limit(5)->get();
foreach ($batches as $b) {
    echo "Batch: {$b->batch_no} | Round: {$b->round_no} | Date: {$b->transfer_date} | Net: {$b->net_amount} | Receipt: {$b->receive_no}\n";
}

$details = SmartMoneyDetail::limit(5)->get();
foreach ($details as $d) {
    echo "Detail: Batch: {$d->batch_no} | Round: {$d->round_no} | HN: {$d->hn} | Name: {$d->pt_name} | Net: {$d->receive_total}\n";
}
