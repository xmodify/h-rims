<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$fileSmartMoney = 'd:/Project Laravel/h-rims/docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx';
$fileStm = 'd:/Project Laravel/h-rims/docs/STM_10989_IPUCS256908_02.xls';

echo "=== 1. ANALYZING SMART MONEY FILE: {$fileSmartMoney} ===\n";
$smSpreadsheet = IOFactory::load($fileSmartMoney);
$smSheet = $smSpreadsheet->getActiveSheet();
$smRows = $smSheet->toArray();
$smSpreadsheet->disconnectWorksheets();

echo "SM Total Rows: " . count($smRows) . "\n";
$smHeader = $smRows[0];
echo "SM Header: " . implode(' | ', array_slice($smHeader, 0, 15)) . "\n";

$smSum = 0;
$smCount = 0;
$smPatients = [];
for ($i = 1; $i < count($smRows); $i++) {
    $r = $smRows[$i];
    if (empty($r[0]) && empty($r[1])) continue;
    $hn = trim((string)($r[1] ?? ''));
    $an = trim((string)($r[2] ?? ''));
    $ptName = trim((string)($r[5] ?? ''));
    $amt = (float)str_replace(',', '', (string)($r[7] ?? 0));
    $rep = trim((string)($r[8] ?? ''));
    $subFund = trim((string)($r[10] ?? ''));
    $smSum += $amt;
    $smCount++;
    $key = $an ?: $hn . '_' . $rep;
    $smPatients[$key] = [
        'hn' => $hn,
        'an' => $an,
        'name' => $ptName,
        'amt' => $amt,
        'rep' => $rep,
        'sub_fund' => $subFund
    ];
}
echo "SM Count: {$smCount}, Sum: " . number_format($smSum, 2) . "\n";

echo "\n=== 2. ANALYZING STM FILE: {$fileStm} ===\n";
$stmSpreadsheet = IOFactory::load($fileStm);
$stmSheetNames = $stmSpreadsheet->getSheetNames();
echo "STM Sheet Names: " . implode(', ', $stmSheetNames) . "\n";

$stmSum = 0;
$stmCount = 0;
$stmPatients = [];

$sheet0 = $stmSpreadsheet->getSheet(0);
$stmRows = $sheet0->toArray();
$stmSpreadsheet->disconnectWorksheets();

echo "STM Sheet 0 Rows: " . count($stmRows) . "\n";
// Find header row in STM
$stmHeaderIdx = 0;
foreach ($stmRows as $idx => $r) {
    $rowStr = implode(' ', array_filter($r, fn($v) => $v !== null && $v !== ''));
    if (stripos($rowStr, 'HN') !== false && stripos($rowStr, 'AN') !== false) {
        $stmHeaderIdx = $idx;
        break;
    }
}

echo "STM Header Row Index: {$stmHeaderIdx}\n";
echo "STM Header: " . implode(' | ', array_slice($stmRows[$stmHeaderIdx], 0, 15)) . "\n";

$colHn = 1;
$colAn = 2;
$colPay = 13; // Usually pay/compensation in STM
foreach ($stmRows[$stmHeaderIdx] as $cIdx => $name) {
    if (trim($name) === 'HN') $colHn = $cIdx;
    if (trim($name) === 'AN') $colAn = $cIdx;
    if (stripos($name, 'ชดเชย') !== false || stripos($name, 'จ่าย') !== false) $colPay = $cIdx;
}

echo "Col HN: {$colHn}, Col AN: {$colAn}, Col Pay: {$colPay}\n";

for ($i = $stmHeaderIdx + 1; $i < count($stmRows); $i++) {
    $r = $stmRows[$i];
    $hn = trim((string)($r[$colHn] ?? ''));
    if (empty($hn) || $hn === 'รวม') continue;
    $an = trim((string)($r[$colAn] ?? ''));
    $pay = (float)str_replace(',', '', (string)($r[$colPay] ?? 0));
    $stmSum += $pay;
    $stmCount++;
    $key = $an ?: $hn;
    $stmPatients[$key] = [
        'hn' => $hn,
        'an' => $an,
        'pay' => $pay,
        'raw' => $r
    ];
}

echo "STM Count: {$stmCount}, Sum: " . number_format($stmSum, 2) . "\n";

echo "\n=== 3. COMPARISON & DIFFERENCE ===\n";
$diff = $stmSum - $smSum;
echo "Difference (STM - SM): " . number_format($diff, 2) . " Baht\n";

// Check which patients exist in STM but not in SM or have different amounts
$missingInSm = [];
$diffAmt = [];
foreach ($stmPatients as $k => $p) {
    if (!isset($smPatients[$k])) {
        $missingInSm[] = $p;
    } else {
        $smP = $smPatients[$k];
        if (abs($p['pay'] - $smP['amt']) > 0.01) {
            $diffAmt[] = [
                'an' => $p['an'],
                'hn' => $p['hn'],
                'stm_pay' => $p['pay'],
                'sm_amt' => $smP['amt'],
                'sub_fund' => $smP['sub_fund'],
                'diff' => $p['pay'] - $smP['amt']
            ];
        }
    }
}

echo "Missing in SM count: " . count($missingInSm) . "\n";
foreach (array_slice($missingInSm, 0, 5) as $m) {
    echo "  - HN: {$m['hn']}, AN: {$m['an']}, Pay: {$m['pay']}\n";
}

echo "Different amounts count: " . count($diffAmt) . "\n";
foreach (array_slice($diffAmt, 0, 10) as $d) {
    echo "  - HN: {$d['hn']}, AN: {$d['an']}, SubFund: {$d['sub_fund']}, STM: {$d['stm_pay']}, SM: {$d['sm_amt']}, Diff: {$d['diff']}\n";
}
