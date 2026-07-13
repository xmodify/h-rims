<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tableName = 'stm_sss_kidney';
if (Schema::hasTable($tableName)) {
    echo "Columns of $tableName:\n";
    $columns = DB::select("SHOW COLUMNS FROM $tableName");
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
} else {
    echo "Table $tableName does not exist.\n";
}
