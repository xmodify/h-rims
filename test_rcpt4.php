<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $tables = DB::connection('hosxp')->select("SHOW TABLES LIKE '%arrear%'");
    echo "Tables related to arrear:\n";
    foreach ($tables as $t) {
        $val = array_values((array)$t)[0];
        echo $val . "\n";
    }

    $finance = DB::connection('hosxp')->select("SHOW TABLES LIKE '%finance_receipt%'");
    echo "Tables related to finance_receipt:\n";
    foreach ($finance as $f) {
        $val = array_values((array)$f)[0];
        echo $val . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
