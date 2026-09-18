<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$allTables = DB::select('SHOW TABLES');
$dbName = env('DB_DATABASE', 'hrims');
$keyName = "Tables_in_" . $dbName;

echo "Searching for '6908_IP' across database tables...\n";
foreach ($allTables as $tObj) {
    $tbl = $tObj->$keyName;
    $cols = Schema::getColumnListing($tbl);
    $textCols = array_filter($cols, function($c) {
        return in_array($c, ['round_no', 'repno', 'stm_filename', 'tran_id', 'projcode', 'documentno', 'doc_no']);
    });
    if (empty($textCols)) continue;

    foreach ($textCols as $col) {
        try {
            $cnt = DB::table($tbl)->where($col, 'like', '%6908_IP%')->count();
            if ($cnt > 0) {
                echo "Found in Table [{$tbl}], Column [{$col}]: count={$cnt}\n";
            }
        } catch (\Exception $e) {}
    }
}
