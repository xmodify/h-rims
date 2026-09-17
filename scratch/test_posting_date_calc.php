<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batches = DB::table('smart_money_batches')->select('batch_no', 'transfer_date', 'budget_year')->distinct()->limit(10)->get();
foreach ($batches as $b) {
    $t = strtotime($b->transfer_date);
    $y = (int)date('Y', $t);
    // If year is < 2400 (CE), add 543. If already >= 2400 (BE), keep it.
    $beYear = $y < 2400 ? ($y + 543) : $y;
    $postingDate = $beYear . date('md', $t);
    echo "Batch: {$b->batch_no}, transfer_date: {$b->transfer_date}, budget_year: {$b->budget_year} => postingDate: {$postingDate}\n";
}
