<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "smart_money_batches:\n";
print_r(Schema::getColumnListing('smart_money_batches'));

echo "\nsmart_money_details:\n";
print_r(Schema::getColumnListing('smart_money_details'));

$countB = DB::table('smart_money_batches')->count();
$countD = DB::table('smart_money_details')->count();
echo "\nTotal Batches: {$countB}, Total Details: {$countD}\n";

$sampleD = DB::table('smart_money_details')->first();
if ($sampleD) {
    echo "Sample smart_money_details record:\n";
    print_r($sampleD);
}
