<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FULL SCHEMA: smart_money_details ===\n";
$cols = DB::select("DESCRIBE smart_money_details");
foreach ($cols as $c) {
    echo sprintf("%-20s | %-15s | Null: %-3s | Default: %s\n", $c->Field, $c->Type, $c->Null, $c->Default ?? 'NULL');
}

echo "\n=== SAMPLE DATA MATCHING 6908_OP_01 in stm_ucs or other STM ===\n";
$stmUcs = DB::table('stm_ucs')->where('round_no', 'like', '%6908_OP_01%')->orWhere('repno', 'like', '%690800037%')->get();
echo "Found in stm_ucs: " . $stmUcs->count() . "\n";
if ($stmUcs->count() > 0) {
    echo "Sample stm_ucs row:\n";
    print_r((array)$stmUcs->first());
}

$stmLgo = DB::table('stm_lgo')->where('round_no', 'like', '%6908_OP_01%')->orWhere('repno', 'like', '%690800037%')->get();
echo "Found in stm_lgo: " . $stmLgo->count() . "\n";
