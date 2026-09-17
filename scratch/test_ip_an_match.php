<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

$ipFile = base_path('docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx');
$sp = IOFactory::load($ipFile);
$sheet = $sp->getActiveSheet();
$rows = $sheet->toArray();

echo "=== CHECKING IP RECORDS IN FILE: " . basename($ipFile) . " ===\n";
$ipRecords = [];
for ($i = 7; $i < count($rows); $i++) {
    $r = $rows[$i];
    if (empty($r[2]) || $r[0] === 'ลำดับที่' || $r[0] === 'รวม') continue;
    $an = trim((string)$r[3]);
    $hn = trim((string)$r[2]);
    $rep = trim((string)$r[9]);
    $net = (float)str_replace(',', '', (string)$r[8]);
    $fund = trim((string)$r[10]) . '/' . trim((string)$r[11]);
    $seq = trim((string)($r[15] ?? ''));
    
    $ipRecords[] = [
        'order' => $r[0],
        'hn' => $hn,
        'an' => $an,
        'cid' => trim((string)$r[5]),
        'name' => trim((string)$r[6]),
        'date' => trim((string)$r[7]),
        'net' => $net,
        'rep' => $rep,
        'fund' => $fund,
        'seq' => $seq
    ];
}

echo "Total IP Records: " . count($ipRecords) . "\n\n";

$ans = array_filter(array_column($ipRecords, 'an'));
echo "Records with AN: " . count($ans) . " / " . count($ipRecords) . " (100% have AN!)\n";
$seqs = array_filter(array_column($ipRecords, 'seq'));
echo "Records with SEQ_NO: " . count($seqs) . " / " . count($ipRecords) . " (IPD does NOT use seq_no, it uses AN!)\n\n";

// Check match in stm_ucs by AN
$matchedInStm = 0;
foreach ($ipRecords as $ip) {
    $stm = DB::table('stm_ucs')->where('an', $ip['an'])->first();
    if ($stm) {
        $matchedInStm++;
        if ($matchedInStm <= 3) {
            echo "Match Sample: AN={$ip['an']}, HN={$ip['hn']}, Name={$ip['name']}, Net in File={$ip['net']} => STM ID={$stm->id}, STM Net={$stm->receive_total}, Round={$stm->round_no}\n";
        }
    }
}
echo "\nTotal Matched in stm_ucs by AN: {$matchedInStm} / " . count($ipRecords) . "\n";
