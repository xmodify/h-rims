<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $record = DB::connection('hosxp')->table('rcpt_print')->where('bill_amount', 0)->where('total_amount', '>', 0)->orderBy('rcpno', 'desc')->limit(1)->first();
    echo "rcpt_print where bill_amount is 0 but total_amount > 0:\n";
    print_r($record);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
