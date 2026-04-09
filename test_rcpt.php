<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $record = DB::connection('hosxp')->table('rcpt_print')->orderBy('vn', 'desc')->limit(1)->first();
    echo "Sample rcpt_print record:\n";
    print_r($record);
    
    $cols = DB::connection('hosxp')->select('SHOW COLUMNS FROM rcpt_print');
    echo "\nColumns in rcpt_print:\n";
    foreach ($cols as $col) {
        if (in_array($col->Field, ['bill_amount', 'total_amount', 'amount', 'vn', 'rcpno'])) {
            echo $col->Field . " - " . $col->Type . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
