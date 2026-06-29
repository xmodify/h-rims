<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$hn = '500034750';
$vstdate = '2025-10-18';

echo "=========================================\n";
echo "Debtor table records for HN {$hn} on {$vstdate}:\n";
echo "=========================================\n";
$debtors = DB::table('debtor_1102050102_801')
    ->where('hn', $hn)
    ->where('vstdate', $vstdate)
    ->get();
foreach ($debtors as $d) {
    echo "VN: {$d->vn} | vsttime: {$d->vsttime} | lgo: {$d->lgo} | kidney: {$d->kidney} | debtor: {$d->debtor} | receive: {$d->receive}\n";
}

echo "\n=========================================\n";
echo "STM LGO table records for HN {$hn} on {$vstdate}:\n";
echo "=========================================\n";
$stmLgo = DB::table('stm_lgo')
    ->where('hn', $hn)
    ->where('vstdate', $vstdate)
    ->get();
foreach ($stmLgo as $s) {
    echo "repno: {$s->repno} | vsttime: {$s->vsttime} | claim_treatment: {$s->claim_treatment} | compensate_treatment: {$s->compensate_treatment}\n";
}

echo "\n=========================================\n";
echo "STM LGO Kidney table records for HN {$hn} on {$vstdate}:\n";
echo "=========================================\n";
// Note: stm_lgo_kidney may use cid or hn
$cid = $debtors->first()->cid ?? '';
$stmKidney = DB::table('stm_lgo_kidney')
    ->where('datetimeadm', $vstdate)
    ->where(function($q) use ($hn, $cid) {
        $q->where('hn', $hn)->orWhere('cid', $cid);
    })
    ->get();
foreach ($stmKidney as $sk) {
    echo "repno: {$sk->repno} | datetimeadm: {$sk->datetimeadm} | compensate_kidney: {$sk->compensate_kidney}\n";
}
