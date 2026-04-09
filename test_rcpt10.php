<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
try {
    $cols = DB::connection('hosxp')->select('SHOW COLUMNS FROM rcpt_arrear_cancel');
    foreach ($cols as $col) echo $col->Field . "\n";
} catch (Exception $e) {}
