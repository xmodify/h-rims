<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dmisRounds = DB::table('stm_seamless_dmis')
    ->select('round_no', DB::raw('count(*) as total_rows'), DB::raw('count(receive_no) as with_receipt'))
    ->groupBy('round_no')
    ->orderByDesc('total_rows')
    ->limit(15)
    ->get();

echo "=== SAMPLE ROUNDS IN stm_seamless_dmis ===\n";
foreach ($dmisRounds as $dr) {
    echo "Round: '{$dr->round_no}' | Total rows: {$dr->total_rows} | With Receipt: {$dr->with_receipt}\n";
}

$sampleDmis = DB::table('stm_seamless_dmis')->first();
echo "\nSample stm_seamless_dmis row:\n";
print_r((array)$sampleDmis);
