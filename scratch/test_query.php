<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pttype = 'S1';
$p = DB::connection('hosxp')->table('pttype as p')
    ->leftJoin('pttype_upp_type as pu', 'pu.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
    ->where('p.pttype', $pttype)
    ->select('p.pttype', 'p.name', 'p.pttype_upp_type_id', 'pu.pttype_upp_type_code')
    ->first();

if ($p) {
    echo "Pttype: {$p->pttype}, Name: {$p->name}, UppTypeId: {$p->pttype_upp_type_id}, Code: '{$p->pttype_upp_type_code}'\n";
} else {
    echo "Pttype S1 not found!\n";
}
