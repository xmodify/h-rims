<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $record = DB::connection('hosxp')->table('rcpt_print')->where('vn', '')->orWhereNull('vn')->orderBy('rcpno', 'desc')->limit(1)->first();
    echo "rcpt_print where vn is empty/null:\n";
    print_r($record);

    $record_abort = DB::connection('hosxp')->select('SHOW COLUMNS FROM rcpt_abort');
    echo "\nColumns in rcpt_abort:\n";
    foreach ($record_abort as $col) {
        if (in_array($col->Field, ['vn', 'rcpno'])) {
            echo $col->Field . " - " . $col->Type . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
