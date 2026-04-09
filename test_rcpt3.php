<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $record = DB::connection('hosxp')->table('patient_arrear_payment')->orderBy('vn', 'desc')->limit(1)->first();
    echo "patient_arrear_payment:\n";
    print_r($record);

    $tables = DB::connection('hosxp')->select("SHOW TABLES LIKE '%arrear%'");
    echo "\nTables related to arrear:\n";
    foreach ($tables as $t) {
        $val = array_values((array)$t)[0];
        echo $val . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
