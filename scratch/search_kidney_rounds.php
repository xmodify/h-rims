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

echo "Searching for 'LGO-HD' or 'DCKD' across database tables...\n";
foreach ($allTables as $tObj) {
    $tbl = $tObj->$keyName;
    $cols = Schema::getColumnListing($tbl);
    $textCols = array_filter($cols, function($c) {
        return in_array($c, ['round_no', 'repno', 'stm_filename', 'tran_id', 'projcode', 'documentno', 'doc_no']);
    });
    if (empty($textCols)) continue;

    foreach ($textCols as $col) {
        try {
            $cnt1 = DB::table($tbl)->where($col, 'like', '%LGO-HD%')->count();
            $cnt2 = DB::table($tbl)->where($col, 'like', '%DCKD%')->count();
            if ($cnt1 > 0 || $cnt2 > 0) {
                echo "Found in Table [{$tbl}], Column [{$col}]: LGO-HD={$cnt1}, DCKD={$cnt2}\n";
            }
        } catch (\Exception $e) {}
    }
}
