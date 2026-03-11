<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::getSchemaBuilder()->getTableListing();
$results = [];

foreach ($tables as $tableName) {
    if (strpos($tableName, 'debtor_') === 0) {
        if (strpos($tableName, '_tracking') !== false) continue;

        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);

        $amountCols = array_intersect($columns, ['debtor', 'receive', 'claim_price', 'receive_total', 'income', 'charge', 'debtor_amount', 'receive_amount']);

        $results[$tableName] = $amountCols;
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
