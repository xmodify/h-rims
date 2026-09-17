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

$tablesWithSeq = [];
$allTables = [];
foreach ($tables as $t) {
    $tName = $t->$prop ?? array_values((array)$t)[0];
    $allTables[] = $tName;
    if (Schema::hasColumn($tName, 'seq_no') || Schema::hasColumn($tName, 'seq') || Schema::hasColumn($tName, 'vn')) {
        $tablesWithSeq[] = $tName;
    }
}

echo "=== TABLES WITH seq_no, seq, or vn (" . count($tablesWithSeq) . " tables) ===\n";
foreach ($tablesWithSeq as $tw) {
    $cols = Schema::getColumnListing($tw);
    $matchedCols = array_intersect($cols, ['seq_no', 'seq', 'vn', 'hn', 'an', 'repno', 'round_no', 'tran_id']);
    echo " - {$tw} => " . implode(', ', $matchedCols) . "\n";
}
