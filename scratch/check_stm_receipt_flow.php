<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$round = '6908_OP_01';
$countInStm = DB::table('stm_ucs')->where('round_no', $round)->count();
$sampleStm = DB::table('stm_ucs')->where('round_no', $round)->first();

echo "Round: {$round}\n";
echo "Total patient rows in stm_ucs: {$countInStm} rows\n";
echo "Sample row before update:\n";
echo " - HN: {$sampleStm->hn}, Name: {$sampleStm->pt_name}, ReceiptNo: " . ($sampleStm->receive_no ?: 'NULL') . "\n";
