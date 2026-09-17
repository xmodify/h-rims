<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$batches = DB::table('smart_money_batches')->where('batch_no', '3167')->get();
echo "=== smart_money_batches for 3167 ===\n";
print_r($batches->toArray());

$details = DB::table('smart_money_details')->where('batch_no', '3167')->limit(2)->get();
echo "\n=== smart_money_details for 3167 ===\n";
print_r($details->toArray());

$excel = DB::table('smart_money_excels')->where('batch_no', '3167')->get();
echo "\n=== smart_money_excels for 3167 ===\n";
print_r($excel->toArray());
