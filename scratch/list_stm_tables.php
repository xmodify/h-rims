<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES LIKE "stm_%"');
foreach ($tables as $t) {
    $arr = (array)$t;
    $name = array_values($arr)[0];
    $count = DB::table($name)->count();
    echo "$name: $count rows\n";
}
