<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = $argv[1] ?? null;
$roundNo = $argv[2] ?? null;

if (!$filePath || !file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

echo "Processing Excel file: $filePath for round: $roundNo\n";

$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

echo "Total rows in sheet: " . count($rows) . "\n";

// Find header row
$headerRowIdx = -1;
$colMap = [];

foreach ($rows as $idx => $row) {
    foreach ($row as $cIdx => $val) {
        $val = trim((string)$val);
        if (str_contains($val, 'HN') || str_contains($val, 'เลขบัตร') || str_contains($val, 'จ่ายชดเชย') || str_contains($val, 'ลำดับ')) {
            $headerRowIdx = $idx;
            break 2;
        }
    }
}

if ($headerRowIdx === -1) {
    echo "Header row not found in Excel!\n";
    exit(1);
}

echo "Found header at row: " . ($headerRowIdx + 1) . "\n";
foreach ($rows[$headerRowIdx] as $cIdx => $name) {
    $name = trim((string)$name);
    if ($name) {
        $colMap[$name] = $cIdx;
    }
}

// Map column indexes by priority
$colIdxSeq = null;
$colIdxRepNo = null;
$colIdxTransId = null;
$colIdxHn = null;
$colIdxAn = null;
$colIdxCid = null;
$colIdxName = null;
$colIdxPtType = null;
$colIdxRegDate = null;
$colIdxVstDate = null;
$colIdxMainFund = null;
$colIdxSubFund = null;
$colIdxSubFundDesc = null;
$colIdxHCode = null;
$colIdxReqAmt = null;
$colIdxNetPaid = null;

foreach ($colMap as $name => $cIdx) {
    $clean = mb_strtolower(preg_replace('/\s+/', ' ', $name));
    
    // 1. Paid Net Amount (Critical: MUST take 'จ่ายชดเชยสุทธิ' and NOT 'ไม่ชดเชย')
    if (str_contains($clean, 'ไม่ชดเชย') || str_contains($clean, 'ไม่จ่าย')) {
        // Explicitly ignore non-compensated column
        continue;
    }
    if (str_contains($clean, 'จ่ายชดเชยสุทธิ') || str_contains($clean, 'ชดเชยสุทธิ') || str_contains($clean, 'จ่ายสุทธิ')) {
        $colIdxNetPaid = $cIdx;
    } elseif ($colIdxNetPaid === null && (str_contains($clean, 'จ่ายชดเชย') || str_contains($clean, 'จ่ายจริง') || str_contains($clean, 'ชดเชย'))) {
        $colIdxNetPaid = $cIdx;
    } elseif ($colIdxNetPaid === null && (str_contains($clean, 'จำนวนเงิน') || str_contains($clean, 'ยอดเงิน'))) {
        $colIdxNetPaid = $cIdx;
    }

    // 2. Trans ID / Invoice No
    if (str_contains($clean, 'trans id') || str_contains($clean, 'transid') || str_contains($clean, 'เลขที่ใบแจ้งหนี้')) {
        $colIdxTransId = $cIdx;
    }

    // 3. Rep No / Round
    if (str_contains($clean, 'rep no') || str_contains($clean, 'repno') || str_contains($clean, 'งวด')) {
        $colIdxRepNo = $cIdx;
    }

    // 4. HN
    if ($clean === 'hn' || preg_match('/\bhn\b/i', $name)) {
        $colIdxHn = $cIdx;
    }

    // 5. AN
    if ($clean === 'an' || preg_match('/\ban\b/i', $name)) {
        $colIdxAn = $cIdx;
    }

    // 6. CID / PID
    if (str_contains($clean, 'เลขบัตรประชาชน') || str_contains($clean, 'บัตรประชาชน') || str_contains($clean, 'pid') || str_contains($clean, 'vctid') || str_contains($clean, 'เลขบัตร')) {
        $colIdxCid = $cIdx;
    }

    // 7. Name
    if (str_contains($clean, 'ชื่อ-สกุล') || str_contains($clean, 'ชื่อ') || str_contains($clean, 'นามสกุล')) {
        $colIdxName = $cIdx;
    }

    // 8. Patient Type / Rights
    if (str_contains($clean, 'สิทธิ์') || str_contains($clean, 'ประเภทผู้ป่วย') || str_contains($clean, 'สิทธิ')) {
        $colIdxPtType = $cIdx;
    }

    // 9. Visit Date
    if (str_contains($clean, 'วันที่เข้ารับบริการ') || str_contains($clean, 'วันรับบริการ') || str_contains($clean, 'vstdate')) {
        $colIdxVstDate = $cIdx;
    } elseif (str_contains($clean, 'วันที่') || str_contains($clean, 'date')) {
        if ($colIdxRegDate === null) $colIdxRegDate = $cIdx;
    }

    // 10. Funds
    if (str_contains($clean, 'sys_code') || str_contains($clean, 'กองทุนหลัก')) {
        $colIdxMainFund = $cIdx;
    }
    if (str_contains($clean, 'item_code') || str_contains($clean, 'subfund') || str_contains($clean, 'กองทุนย่อย')) {
        $colIdxSubFund = $cIdx;
    }
    if (str_contains($clean, 'ความหมาย subfund') || str_contains($clean, 'รายการ/ประเภทที่ขอเบิก') || str_contains($clean, 'รายการที่ขอเบิก')) {
        $colIdxSubFundDesc = $cIdx;
    }

    // 11. HCode
    if (str_contains($clean, 'hcode') || str_contains($clean, 'รหัสหน่วยบริการ')) {
        $colIdxHCode = $cIdx;
    }

    // 12. Sequence
    if (str_contains($clean, 'ลำดับที่') || str_contains($clean, 'ลำดับ')) {
        if ($colIdxSeq === null) $colIdxSeq = $cIdx;
    }
}

echo "Mapped Columns:\n";
echo " - Net Paid: Col $colIdxNetPaid\n";
echo " - HN: Col $colIdxHn\n";
echo " - AN: Col $colIdxAn\n";
echo " - CID: Col $colIdxCid\n";
echo " - Name: Col $colIdxName\n";
echo " - Visit Date: Col $colIdxVstDate\n";
echo " - Trans ID: Col $colIdxTransId\n";
echo " - Rep No: Col $colIdxRepNo\n";
echo " - SubFund: Col $colIdxSubFund\n";

// Find batch info from DB if possible
$batch = SmartMoneyBatch::where('round_no', $roundNo)->first();
$batchNo = $batch ? $batch->batch_no : null;
$transferDate = $batch ? $batch->transfer_date : null;
$budgetYear = $batch ? $batch->budget_year : null;

$inserted = 0;
$totalAmount = 0;
$lastHnByCid = [];

for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    
    // Check if empty row
    if (empty(array_filter($row))) continue;
    
    // Check total row
    $firstCol = trim((string)($row[0] ?? ''));
    if (str_contains($firstCol, 'รวม') || str_contains($firstCol, 'Total')) continue;

    $seqNo = $colIdxSeq !== null ? trim((string)($row[$colIdxSeq] ?? '')) : null;
    $repNo = $colIdxRepNo !== null ? trim((string)($row[$colIdxRepNo] ?? '')) : $roundNo;
    $transId = $colIdxTransId !== null ? trim((string)($row[$colIdxTransId] ?? '')) : null;
    $hn = $colIdxHn !== null ? trim((string)($row[$colIdxHn] ?? '')) : '';
    $an = $colIdxAn !== null ? trim((string)($row[$colIdxAn] ?? '')) : null;
    $cid = $colIdxCid !== null ? trim((string)($row[$colIdxCid] ?? '')) : '';
    $name = $colIdxName !== null ? trim((string)($row[$colIdxName] ?? '')) : '';
    $ptType = $colIdxPtType !== null ? trim((string)($row[$colIdxPtType] ?? '')) : '';
    $mainFund = $colIdxMainFund !== null ? trim((string)($row[$colIdxMainFund] ?? '')) : '';
    $subFund = $colIdxSubFund !== null ? trim((string)($row[$colIdxSubFund] ?? '')) : '';
    $subFundDesc = $colIdxSubFundDesc !== null ? trim((string)($row[$colIdxSubFundDesc] ?? '')) : '';
    $hcode = $colIdxHCode !== null ? trim((string)($row[$colIdxHCode] ?? '')) : '10989';

    // Parse Visit Date
    $vstRaw = '';
    if ($colIdxVstDate !== null && !empty($row[$colIdxVstDate])) {
        $vstRaw = trim((string)$row[$colIdxVstDate]);
    } elseif ($colIdxRegDate !== null && !empty($row[$colIdxRegDate])) {
        $vstRaw = trim((string)$row[$colIdxRegDate]);
    }

    $vstdate = null;
    if ($vstRaw) {
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $vstRaw, $dm)) {
            $y = (int)$dm[3];
            if ($y > 2500) $y -= 543;
            $vstdate = sprintf('%04d-%02d-%02d', $y, $dm[2], $dm[1]);
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vstRaw)) {
            $vstdate = $vstRaw;
        }
    }

    // Parse Paid Amount
    $amount = 0.00;
    if ($colIdxNetPaid !== null && isset($row[$colIdxNetPaid])) {
        $cleanAmt = str_replace(',', '', trim((string)$row[$colIdxNetPaid]));
        if (is_numeric($cleanAmt)) {
            $amount = (float)$cleanAmt;
        }
    }

    // If HN is empty but CID has a known HN from previous row, use it
    if (!empty($cid)) {
        if (!empty($hn)) {
            $lastHnByCid[$cid] = $hn;
        } elseif (isset($lastHnByCid[$cid])) {
            $hn = $lastHnByCid[$cid];
        }
    }

    if (!$hn && !$cid && empty($name)) continue;

    // Use unique identifier for match: round_no + (transId or seq_no or (hn + vstdate + subFund))
    $matchAttributes = [
        'round_no' => $roundNo,
    ];
    if ($transId) {
        $matchAttributes['invoice_no'] = $transId;
    } elseif ($seqNo) {
        $matchAttributes['seq_no'] = $seqNo;
    } else {
        $matchAttributes['hn'] = $hn;
        if ($vstdate) $matchAttributes['vstdate'] = $vstdate;
        if ($subFund) $matchAttributes['sub_fund'] = $subFund;
    }

    SmartMoneyDetail::updateOrCreate(
        $matchAttributes,
        [
            'batch_no' => $batchNo,
            'transfer_date' => $transferDate,
            'hn' => $hn,
            'an' => $an,
            'cid' => $cid,
            'pt_name' => $name,
            'pt_type' => $ptType,
            'vstdate' => $vstdate,
            'receive_total' => $amount,
            'repno' => $repNo,
            'main_fund' => $mainFund,
            'sub_fund' => $subFund,
            'sub_fund_desc' => $subFundDesc,
            'hcode' => $hcode ?: '10989',
            'seq_no' => $seqNo,
            'invoice_no' => $transId,
            'budget_year' => $budgetYear,
        ]
    );

    $totalAmount += $amount;
    $inserted++;
}

echo "Successfully imported $inserted detail records into smart_money_details table!\n";
echo "Total Sum: " . number_format($totalAmount, 2) . " THB\n";
