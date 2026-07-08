<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Query visits in ovst or vn_stat where claim_price is 303.75, 692.50, 320.00, etc.
$prices = [303.75, 692.50, 320.00, 220.00, 3288.75, 331.00, 843.00];

foreach ($prices as $p) {
    echo "--- Price: $p ---\n";
    $vns = DB::connection('hosxp')
        ->table('vn_stat')
        ->where('income', $p)
        ->get();
    foreach ($vns as $v) {
        $billtran = DB::connection('hosxp')->table('ovst_sss_billtran')->where('vn', $v->vn)->first();
        $rep = DB::table('sss_ssop_rep')->where('vn', $billtran ? $billtran->invno : '')->first();
        $stm = DB::table('sss_ssop_stm')->where('invno', $billtran ? $billtran->invno : '')->first();
        echo "VN: {$v->vn}, VstDate: {$v->vstdate}, Invno: " . ($billtran ? $billtran->invno : 'none') 
             . ", REP: " . ($rep ? $rep->error_codes : 'none') 
             . ", STM: " . ($stm ? $stm->total : 'none') . "\n";
    }
}
