<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Check mapping without price_ipd
    echo "\n--- Sample mapping for individual lab item and nondrugitems ---\n";
    $item = DB::connection('hosxp')->select("
        SELECT l.lab_items_code, l.icode, l.lab_items_name, l.service_price, 
               n.name AS nondrug_name, n.price AS nondrug_price, n.price2 AS nondrug_price2, n.price3 AS nondrug_price3
        FROM lab_items l
        INNER JOIN nondrugitems n ON n.icode = l.icode
        LIMIT 3
    ");
    print_r($item);

    echo "\n--- Sample mapping for panel lab item and nondrugitems ---\n";
    $panel = DB::connection('hosxp')->select("
        SELECT sg.lab_items_sub_group_code, sg.group_icode, sg.lab_items_sub_group_name, sg.group_price, 
               n.name AS nondrug_name, n.price AS nondrug_price, n.price2 AS nondrug_price2, n.price3 AS nondrug_price3
        FROM lab_items_sub_group sg
        INNER JOIN nondrugitems n ON n.icode = sg.group_icode
        LIMIT 3
    ");
    print_r($panel);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
