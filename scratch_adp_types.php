<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$icode = '3003451';
echo "Checking pttype_items_price again..." . PHP_EOL;

$cols = DB::connection('hosxp')->select("SHOW COLUMNS FROM pttype_items_price");
foreach ($cols as $c) {
    echo "  Field: {$c->Field} | Type: {$c->Type}" . PHP_EOL;
}

$rows = DB::connection('hosxp')->select("
    SELECT * FROM pttype_items_price 
    WHERE items_table_code = '3003451' 
       OR items_table_code_int = 3003451
");
echo "Matches found: " . count($rows) . PHP_EOL;
print_r($rows);

$allNDI = DB::connection('hosxp')->select("
    SELECT * FROM pttype_items_price 
    WHERE items_table_name = 'nondrugitems'
    LIMIT 10
");
echo "nondrugitems matches in pttype_items_price:" . PHP_EOL;
print_r($allNDI);
