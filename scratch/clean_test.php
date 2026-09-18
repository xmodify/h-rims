<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Models\SmartMoneyBatch::where('batch_no', '3271')->delete();
App\Models\SmartMoneyDetail::where('batch_no', '3271')->delete();
echo "Cleaned test batch 3271\n";
