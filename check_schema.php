<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$hrims_tables = ['hrims.lookup_icode', 'hrims.stm_ucs', 'hrims.lookup_hospcode'];
$hosxp_tables = ['ovst', 'opitemrece', 'patient', 'visit_pttype', 'pttype', 'vn_stat'];

function check_tables($connection, $tables)
{
    echo "Connection: $connection\n";
    foreach ($tables as $table) {
        echo "Table: $table\n";
        try {
            $cols = DB::connection($connection)->select("DESCRIBE $table");
            foreach ($cols as $col) {
                echo "  - " . $col->Field . " (" . $col->Type . ")\n";
            }
            $idxs = DB::connection($connection)->select("SHOW INDEX FROM $table");
            echo "  Indexes:\n";
            foreach ($idxs as $idx) {
                echo "    - " . $idx->Key_name . ": " . $idx->Column_name . "\n";
            }
        } catch (\Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
        echo "---------------------------------\n";
    }
}

check_tables('mysql', $hrims_tables);
check_tables('hosxp', $hosxp_tables);
