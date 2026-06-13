<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$showTables = DB::select("SHOW TABLES");
$tables = [];
foreach ($showTables as $row) {
    $tables[] = array_values((array)$row)[0];
}

$schemaDump = [];

foreach ($tables as $table) {
    $tableKey = $table;
    
    // Get columns
    $columns = DB::select("SHOW COLUMNS FROM `$table`");
    $colsDef = [];
    foreach ($columns as $col) {
        $colsDef[$col->Field] = [
            'type' => $col->Type,
            'nullable' => $col->Null === 'YES',
            'default' => $col->Default,
            'extra' => $col->Extra
        ];
    }
    
    // Get indexes
    $indexes = DB::select("SHOW INDEX FROM `$table`");
    $idxDef = [];
    foreach ($indexes as $idx) {
        $idxDef[$idx->Key_name][] = [
            'column' => $idx->Column_name,
            'unique' => $idx->Non_unique == 0
        ];
    }
    
    $schemaDump[$tableKey] = [
        'columns' => $colsDef,
        'indexes' => $idxDef
    ];
}

$outPath = __DIR__ . '/extracted_schemas.json';
file_put_contents($outPath, json_encode($schemaDump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully dumped schema of " . count($tables) . " tables to extracted_schemas.json\n";
