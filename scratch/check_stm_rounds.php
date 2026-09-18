<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CHECKING ROUND NUMBERS IN STM TABLES ===\n";
$tables = [
    'stm_ucs', 'stm_ofc', 'stm_lgo', 'stm_ucs_kidney', 'stm_lgo_kidney', 
    'stm_seamless_dmis', 'stm_bkk', 'stm_bmt', 'stm_srt', 'stm_pvt'
];

foreach ($tables as $t) {
    if (Schema::hasTable($t)) {
        $count = DB::table($t)->count();
        $distinctRounds = DB::table($t)->whereNotNull('round_no')->where('round_no', '<>', '')->distinct()->pluck('round_no')->take(5)->toArray();
        $sampleFilename = DB::table($t)->whereNotNull('stm_filename')->value('stm_filename');
        echo "Table: {$t} (Count: {$count})\n";
        echo "  - Sample Rounds: " . implode(', ', $distinctRounds) . "\n";
        echo "  - Sample File: {$sampleFilename}\n";
    }
}
