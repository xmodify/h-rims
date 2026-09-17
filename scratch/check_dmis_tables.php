<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select("SHOW TABLES");
$dbName = env('DB_DATABASE', 'hrims');
$prop = "Tables_in_" . $dbName;

echo "=== TABLES RELATED TO DMIS / SEAMLESS ===\n";
foreach ($tables as $t) {
    $tName = $t->$prop ?? array_values((array)$t)[0];
    if (stripos($tName, 'dmis') !== false || stripos($tName, 'seamless') !== false) {
        $cols = Schema::getColumnListing($tName);
        $hasRound = in_array('round_no', $cols) ? 'YES' : 'NO';
        $hasReceive = in_array('receive_no', $cols) ? 'YES' : 'NO';
        $hasReceiptDate = in_array('receipt_date', $cols) ? 'YES' : 'NO';
        $hasReceiptBy = in_array('receipt_by', $cols) ? 'YES' : 'NO';
        $count = DB::table($tName)->count();
        echo "Table: {$tName} ({$count} rows) | round_no: {$hasRound} | receive_no: {$hasReceive} | receipt_date: {$hasReceiptDate} | receipt_by: {$hasReceiptBy}\n";
    }
}
