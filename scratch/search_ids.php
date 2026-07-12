<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::connection('hosxp')->select("SHOW COLUMNS FROM debtor_dt");
foreach ($columns as $c) {
    echo "debtor_dt col: " . $c->Field . "\n";
}

// Let's search for 285040 and 285041 in the database tables that we found earlier:
// rcpt_debt, rcpt_print, rcpt_debt_detail, iclaim_rcpt_debt, debtor_dt
$tables = ['rcpt_debt', 'rcpt_print', 'rcpt_debt_detail', 'iclaim_rcpt_debt', 'debtor_dt'];
foreach ($tables as $table) {
    $cols = DB::connection('hosxp')->select("SHOW COLUMNS FROM $table");
    foreach ($cols as $col) {
        $name = $col->Field;
        try {
            $check = DB::connection('hosxp')->table($table)->where($name, 285040)->first();
            if ($check) {
                echo "FOUND 285040 in table: $table, col: $name\n";
            }
            $check2 = DB::connection('hosxp')->table($table)->where($name, 285041)->first();
            if ($check2) {
                echo "FOUND 285041 in table: $table, col: $name\n";
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
}
