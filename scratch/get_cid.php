<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$row = DB::table('debtor_1102050102_801')->where('vn', '681018111805')->first();
if ($row) {
    echo "Patient Info:\n";
    echo "  - VN: {$row->vn}\n";
    echo "  - HN: {$row->hn}\n";
    echo "  - CID: {$row->cid}\n";
    echo "  - ptname: {$row->ptname}\n";
    echo "  - vstdate: {$row->vstdate}\n";
    echo "  - vsttime: {$row->vsttime}\n";
} else {
    echo "Record not found\n";
}
