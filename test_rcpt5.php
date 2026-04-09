<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $cols = DB::connection('hosxp')->select('SHOW COLUMNS FROM rcpt_arrear');
    echo "Columns in rcpt_arrear:\n";
    foreach ($cols as $col) {
        if (in_array($col->Field, ['vn', 'rcpno', 'bill_amount', 'total_amount', 'amount'])) {
            echo $col->Field . " - " . $col->Type . "\n";
        }
    }

    $sample = DB::connection('hosxp')->table('rcpt_arrear')->orderBy('rcpno', 'desc')->limit(1)->first();
    echo "\nSample rcpt_arrear record:\n";
    print_r($sample);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
