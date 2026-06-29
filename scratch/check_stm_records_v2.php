<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$hn = '500034750';
$cid = '3341800169304';
$vstdate = '2025-10-18';

echo "Columns of stm_lgo:\n";
print_r(Schema::getColumnListing('stm_lgo'));

echo "\nColumns of stm_lgo_kidney:\n";
print_r(Schema::getColumnListing('stm_lgo_kidney'));

echo "\n=========================================\n";
echo "STM LGO table records for HN {$hn} / CID {$cid} on {$vstdate}:\n";
echo "=========================================\n";
$stmLgo = DB::table('stm_lgo')
    ->where('vstdate', $vstdate)
    ->where(function($q) use ($hn, $cid) {
        $q->where('hn', $hn)->orWhere('cid', $cid);
    })
    ->get();
foreach ($stmLgo as $s) {
    $arr = (array)$s;
    echo "repno: " . ($arr['repno'] ?? 'N/A') . " | vsttime: " . ($arr['vsttime'] ?? 'N/A') . " | compensate: " . ($arr['compensate_treatment'] ?? 'N/A') . "\n";
}

echo "\n=========================================\n";
echo "STM LGO Kidney table records for HN {$hn} / CID {$cid} on {$vstdate}:\n";
echo "=========================================\n";
$stmKidney = DB::table('stm_lgo_kidney')
    ->where('datetimeadm', $vstdate)
    ->where(function($q) use ($hn, $cid) {
        $q->where('hn', $hn)->orWhere('cid', $cid);
    })
    ->get();
foreach ($stmKidney as $sk) {
    $arr = (array)$sk;
    echo "repno: " . ($arr['repno'] ?? 'N/A') . " | datetimeadm: " . ($arr['datetimeadm'] ?? 'N/A') . " | compensate_kidney: " . ($arr['compensate_kidney'] ?? 'N/A') . "\n";
}
