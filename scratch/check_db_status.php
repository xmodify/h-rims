<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

echo "Batches total: " . SmartMoneyBatch::count() . "\n";
foreach (SmartMoneyBatch::orderBy('id', 'desc')->take(10)->get() as $b) {
    $detailCount = SmartMoneyDetail::where('batch_no', $b->batch_no)->orWhere('round_no', $b->round_no)->count();
    $detailSum = SmartMoneyDetail::where('batch_no', $b->batch_no)->orWhere('round_no', $b->round_no)->sum('receive_total');
    echo "ID: {$b->id} | Batch: {$b->batch_no} | Round: {$b->round_no} | Fund: {$b->fund_name} | Net: {$b->net_amount} | Details: {$detailCount} | Sum: {$detailSum}\n";
}

echo "\nTotal SmartMoneyDetail count: " . SmartMoneyDetail::count() . "\n";
echo "LGO Batches count: " . SmartMoneyBatch::where('round_no', 'like', '%LGO%')->count() . "\n";
echo "LGO Details count: " . SmartMoneyDetail::where('round_no', 'like', '%LGO%')->count() . "\n";
foreach (SmartMoneyBatch::where('round_no', 'like', '%LGO%')->get() as $b) {
    echo "Batch: {$b->batch_no}, Round: {$b->round_no}, Net: {$b->net_amount}\n";
}

echo "Current count: " . SmartMoneyDetail::where('batch_no', '3270')->count() . "\n";
echo "Current sum: " . SmartMoneyDetail::where('batch_no', '3270')->sum('receive_total') . "\n";

echo "\nTesting getDetailJson for Batch 3270:\n";
$c = new \App\Http\Controllers\SmartMoneyController();
$req = new \Illuminate\Http\Request();
$res = $c->getDetailJson($req, '3270');
$data = json_decode($res->getContent(), true);
echo "Status: " . $data['status'] . "\n";
echo "Batch info: " . json_encode($data['batch'], JSON_UNESCAPED_UNICODE) . "\n";
echo "Stats: " . json_encode($data['stats'], JSON_UNESCAPED_UNICODE) . "\n";
echo "Items returned: " . count($data['data']) . "\n";
if (!empty($data['data'])) {
    echo "First item in modal:\n";
    print_r($data['data'][0]);
}
