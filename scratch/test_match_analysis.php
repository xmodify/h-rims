<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

$files = glob(base_path('docs/*6908_OP*.xlsx'));
$file = $files[0];
$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();
$rows = $sheet->toArray();

$excelRows = [];
for ($i = 7; $i < count($rows); $i++) {
    $r = $rows[$i];
    if (empty($r[2])) continue; // HN
    
    // Parse vstdate dd/mm/yyyy to Y-m-d
    $rawVst = trim((string)$r[7]);
    $vstdate = null;
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $rawVst, $m)) {
        $y = (int)$m[3];
        if ($y > 2400) $y -= 543;
        $vstdate = sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[1]);
    }
    
    $excelRows[] = [
        'order' => $r[0],
        'transfer_date' => $r[1],
        'hn' => trim((string)$r[2]),
        'an' => trim((string)$r[3]),
        'pt_type' => trim((string)$r[4]),
        'cid' => trim((string)$r[5]),
        'pt_name' => trim((string)$r[6]),
        'vstdate_raw' => $rawVst,
        'vstdate' => $vstdate,
        'receive_total' => (float)str_replace(',', '', (string)$r[8]),
        'repno' => trim((string)$r[9]),
        'main_fund' => trim((string)$r[10]),
        'sub_fund' => trim((string)$r[11]),
        'sub_fund_desc' => trim((string)$r[12]),
        'hsend' => trim((string)($r[13] ?? '')),
        'hcode' => trim((string)($r[14] ?? '')),
        'seq_no' => trim((string)($r[15] ?? '')),
        'invoice_no' => trim((string)($r[16] ?? '')),
        'invoice_lt' => trim((string)($r[17] ?? '')),
    ];
}

echo "Total Excel Data Rows: " . count($excelRows) . "\n";
$repNosInExcel = array_unique(array_column($excelRows, 'repno'));
echo "Distinct REP_NOs in Excel: " . implode(', ', $repNosInExcel) . "\n\n";

// Match against stm_ucs
$stmRows = DB::table('stm_ucs')->where('round_no', '6908_OP_01')->get();
echo "Total rows in stm_ucs for 6908_OP_01: " . $stmRows->count() . "\n";
$repNosInStm = $stmRows->pluck('repno')->unique()->filter()->values()->toArray();
echo "Distinct REP_NOs in stm_ucs: " . implode(', ', $repNosInStm) . "\n\n";

// Matching Analysis
$matchByHnVstdate = 0;
$matchByCidVstdate = 0;
$matchByHnOnly = 0;
$matchByRepnoAndHn = 0;

$stmLookup = [];
foreach ($stmRows as $s) {
    $stmLookup[$s->hn . '_' . $s->vstdate][] = $s;
    $stmLookup['rep_' . $s->repno . '_' . $s->hn][] = $s;
}

foreach ($excelRows as $ex) {
    $key1 = $ex['hn'] . '_' . $ex['vstdate'];
    if (isset($stmLookup[$key1])) {
        $matchByHnVstdate++;
    }
    $key2 = 'rep_' . $ex['repno'] . '_' . $ex['hn'];
    if (isset($stmLookup[$key2])) {
        $matchByRepnoAndHn++;
    }
}

echo "--- MATCH RESULTS ---\n";
echo "Match by (HN + Vstdate): {$matchByHnVstdate} / " . count($excelRows) . "\n";
echo "Match by (REP_NO + HN): {$matchByRepnoAndHn} / " . count($excelRows) . "\n";

// Check sample match details
echo "\n--- 5 SAMPLE MATCHES ---\n";
$sampleCount = 0;
foreach ($excelRows as $ex) {
    $key1 = $ex['hn'] . '_' . $ex['vstdate'];
    if (isset($stmLookup[$key1])) {
        $stmMatch = $stmLookup[$key1][0];
        echo "Excel Row: HN={$ex['hn']}, CID={$ex['cid']}, VSTDATE={$ex['vstdate']}, REPNO={$ex['repno']}, SEQ={$ex['seq_no']}, Amount={$ex['receive_total']}\n";
        echo "   STM Match: ID={$stmMatch->id}, TRAN_ID={$stmMatch->tran_id}, REPNO={$stmMatch->repno}, ReceiveTotal={$stmMatch->receive_total}\n";
        $sampleCount++;
        if ($sampleCount >= 5) break;
    }
}
