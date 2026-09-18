<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== stm_ucs columns ===\n";
print_r(Schema::getColumnListing('stm_ucs'));

echo "=== smart_money_details columns ===\n";
print_r(Schema::getColumnListing('smart_money_details'));

echo "=== Sample from stm_ucs ===\n";
$sample = DB::table('stm_ucs')->first();
print_r($sample);
