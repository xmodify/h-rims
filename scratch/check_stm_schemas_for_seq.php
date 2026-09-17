<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_ofc',
    'stm_ofc_csop',
    'stm_ofc_cipn',
    'stm_bkk',
    'stm_bmt',
    'stm_srt',
    'stm_pvt',
    'stm_lgo',
    'stm_sss_kidney',
    'smart_money_details',
    'smart_money_batches'
];

echo "=== CHECKING TABLE SCHEMAS FOR SEQ / STM FIELDS ===\n";
foreach ($tables as $tbl) {
    if (Schema::hasTable($tbl)) {
        $cols = Schema::getColumnListing($tbl);
        echo "Table: {$tbl} (" . count($cols) . " cols)\n";
        
        // Find relevant columns: seq, hn, an, pid, cid, repno, round, amount, receipt, etc.
        $relevant = array_filter($cols, function($c) {
            return preg_match('/seq|hn|an|cid|pid|rep|round|trans|vst|money|pay|net|rcpt|rec|inv/i', $c);
        });
        echo "  Relevant cols: " . implode(', ', $relevant) . "\n";
    } else {
        echo "Table: {$tbl} NOT FOUND\n";
    }
}
