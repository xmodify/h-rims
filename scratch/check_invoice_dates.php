<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Query a few visits in ovst_sss_billtran and see their vstdate
echo "--- ovst_sss_billtran samples ---\n";
$bills = DB::connection('hosxp')
    ->table('ovst_sss_billtran')
    ->whereIn('invno', ['274381', '277852', '277724'])
    ->get();
foreach ($bills as $b) {
    $vst = DB::connection('hosxp')->table('ovst')->where('vn', $b->vn)->first();
    echo "Invno: {$b->invno}, VN: {$b->vn}, VstDate: " . ($vst ? $vst->vstdate : 'N/A') . "\n";
}

echo "\n--- sss_ssop_rep samples ---\n";
$reps = DB::table('sss_ssop_rep')
    ->whereIn('vn', ['274381', '277852'])
    ->get();
foreach ($reps as $r) {
    echo "VN (Invno): {$r->vn}, dttran: {$r->dttran}, error: {$r->error_codes}\n";
}
