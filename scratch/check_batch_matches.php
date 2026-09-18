<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\SmartMoneyBatch;

$sampleBatches = SmartMoneyBatch::all();
echo "Batches in DB:\n";
foreach ($sampleBatches as $b) {
    echo "Batch: {$b->batch_no}, Round: {$b->round_no}\n";
    // Check in stm_ucs
    $ucsCount = DB::table('stm_ucs')->where('round_no', $b->round_no)->count();
    $lgoCount = DB::table('stm_lgo')->where('round_no', $b->round_no)->count();
    $ofcCount = DB::table('stm_ofc')->where('round_no', $b->round_no)->count();
    echo "  Matches in stm_ucs: {$ucsCount}, stm_lgo: {$lgoCount}, stm_ofc: {$ofcCount}\n";
}
