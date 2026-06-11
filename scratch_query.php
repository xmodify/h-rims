<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::connection('hosxp')->selectOne("SELECT price, price2, price3, ipd_price, ipd_price2, ipd_price3 FROM nondrugitems WHERE icode = '3003451'");
echo "Nondrugitem 3003451:\n";
print_r($row);

$overrides = DB::connection('hosxp')->select("SELECT * FROM pttype_items_price WHERE items_table_code = '3003451' OR items_table_code_int = 3003451");
echo "Overrides:\n";
print_r($overrides);
