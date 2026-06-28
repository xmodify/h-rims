<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDmisController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'นำเข้าข้อมูล DMIS'], 403);
                }
                return $next($request);
            }
        ]);
    }

    public function index(Request $request)
    {
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_dmis = [];
        $claim_types = [];
        
        if (Schema::hasTable('stm_seamless_dmis')) {
            $stm_dmis = DB::select("
                SELECT 
                    excel_filename,
                    round_no,
                    dmis_group,
                    MAX(claim_type_name) AS claim_type_name,
                    COUNT(DISTINCT repno) AS rep_count,
                    COUNT(id) AS count_rows,
                    SUM(claim_price) AS claim_price,
                    SUM(receive_total) AS receive_total,
                    MAX(receive_no) AS receive_no,
                    MAX(receipt_date) AS receipt_date,
                    MAX(receipt_by) AS receipt_by
                FROM stm_seamless_dmis
                WHERE (YEAR(vstdate) + 543 + IF(MONTH(vstdate) >= 10, 1, 0)) = ?
                GROUP BY excel_filename, round_no, dmis_group
                ORDER BY round_no DESC, excel_filename DESC
            ", [$budget_year]);

            $claim_types = DB::table('stm_seamless_dmis')
                ->whereNotNull('claim_type_name')
                ->where('claim_type_name', '<>', '')
                ->distinct()
                ->orderBy('claim_type_name')
                ->pluck('claim_type_name')
                ->toArray();
        }

        return view('import.dmis_index', compact('budget_year_select', 'budget_year', 'budget_year_now', 'stm_dmis', 'claim_types'));
    }

    public function detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        $claim_type = $request->claim_type ?: '';

        $claim_types = [];
        if (Schema::hasTable('stm_seamless_dmis')) {
            $claim_types = DB::table('stm_seamless_dmis')
                ->whereNotNull('claim_type_name')
                ->where('claim_type_name', '<>', '')
                ->distinct()
                ->orderBy('claim_type_name')
                ->pluck('claim_type_name')
                ->toArray();
        }

        if ($request->ajax() || $request->export == 'excel') {
            $query = DB::table('stm_seamless_dmis')
                ->whereDate('vstdate', '>=', $start_date)
                ->whereDate('vstdate', '<=', $end_date);

            if (!empty($claim_type)) {
                $query->where('claim_type_name', $claim_type);
            }

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('hn', 'like', "%$search%")
                        ->orWhere('an', 'like', "%$search%")
                        ->orWhere('ptname', 'like', "%$search%")
                        ->orWhere('cid', 'like', "%$search%")
                        ->orWhere('trans_id', 'like', "%$search%");
                });
            }

            // Export Excel
            if ($request->export == 'excel') {
                $records = $query->orderByDesc('vstdate')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $headers = ['วันที่รับบริการ', 'ประเภทกิจกรรม', 'เลขธุรกรรม (Trans ID)', 'HN', 'AN', 'เลขบัตรประชาชน', 'ชื่อ-สกุลผู้ป่วย', 'ยอดขอเบิก', 'ร้อยละจ่าย', 'ชดเชยจริง', 'Deny Code', 'คำอธิบายปฏิเสธ'];
                $sheet->fromArray($headers, null, 'A1');

                $row = 2;
                foreach ($records as $item) {
                    $sheet->setCellValue('A' . $row, $item->vstdate ?: '-');
                    $sheet->setCellValue('B' . $row, $item->claim_type_name ?: '-');
                    $sheet->setCellValueExplicit('C' . $row, $item->trans_id ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('D' . $row, $item->hn ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('E' . $row, $item->an ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('F' . $row, $item->cid ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('G' . $row, $item->ptname ?: '-');
                    $sheet->setCellValue('H' . $row, $item->claim_price);
                    $sheet->setCellValue('I' . $row, $item->pay_percent);
                    $sheet->setCellValue('J' . $row, $item->receive_total);
                    $sheet->setCellValue('K' . $row, $item->deny_code ?: '-');
                    $sheet->setCellValue('L' . $row, $item->deny_warning ?: '-');
                    $row++;
                }

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $fileName = 'dmis_detail_export_' . date('Ymd_His') . '.xlsx';
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                $writer->save('php://output');
                exit;
            }

            $totalData = $query->count();
            $limit = $request->input('length') ?: 10;
            $start = $request->input('start') ?: 0;

            $records = $query->orderByDesc('vstdate')
                ->offset($start)
                ->limit($limit)
                ->get();

            $data = [];
            foreach ($records as $r) {
                $data[] = [
                    'vstdate' => $r->vstdate ? date('d/m/Y', strtotime($r->vstdate)) : '-',
                    'claim_type_name' => $r->claim_type_name ?: '-',
                    'trans_id' => $r->trans_id ?: '-',
                    'hn' => $r->hn ?: '-',
                    'cid' => $r->cid ?: '-',
                    'ptname' => $r->ptname ?: '-',
                    'claim_price' => number_format($r->claim_price, 2),
                    'pay_percent' => $r->pay_percent ? number_format($r->pay_percent) . '%' : '-',
                    'receive_total' => number_format($r->receive_total, 2),
                    'deny_code' => $r->deny_code ? '<span class="badge bg-danger">' . $r->deny_code . '</span>' : '-',
                    'deny_warning' => $r->deny_warning ?: '-',
                ];
            }

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data" => $data
            ]);
        }

        return view('import.dmis_detail', compact('start_date', 'end_date', 'claim_types', 'claim_type'));
    }

    public function getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $claim_type = $request->claim_type;

        $query = DB::table('stm_seamless_dmis')
            ->select(
                DB::raw('MONTH(vstdate) as month_no'),
                DB::raw('SUM(claim_price) as total_claim'),
                DB::raw('SUM(receive_total) as total_receive')
            )
            ->whereRaw('(YEAR(vstdate) + 543 + IF(MONTH(vstdate) >= 10, 1, 0)) = ?', [$budget_year]);

        if (!empty($claim_type)) {
            $query->where('claim_type_name', $claim_type);
        }

        $rawData = $query->groupBy(DB::raw('MONTH(vstdate)'))->get()->keyBy('month_no');

        // Order months from Oct (10) to Sep (9)
        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 
            11 => 'พ.ย. ' . $prevByShort, 
            12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 
            2 => 'ก.พ. ' . $byShort, 
            3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 
            5 => 'พ.ค. ' . $byShort, 
            6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 
            8 => 'ส.ค. ' . $byShort, 
            9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $claimPrices = [];
        $receiveTotals = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            if (isset($rawData[$m])) {
                $claimPrices[] = floatval($rawData[$m]->total_claim);
                $receiveTotals[] = floatval($rawData[$m]->total_receive);
            } else {
                $claimPrices[] = 0.00;
                $receiveTotals[] = 0.00;
            }
        }

        return response()->json([
            'labels' => $labels,
            'claim_prices' => $claimPrices,
            'receive_totals' => $receiveTotals
        ]);
    }

    public function save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'files' => 'required|array|max:15',
            'files.*' => 'required|file'
        ]);

        if (!Schema::hasTable('stm_seamless_dmis')) {
            return redirect()->back()->with('error', 'ไม่พบตาราง stm_seamless_dmis กรุณากดปุ่ม Upgrade Structure ในหน้าตั้งค่าหลัก');
        }

        $uploadedFiles = $request->file('files');
        $successFiles = [];
        $failedFiles = [];
        $totalInserted = 0;
        $totalUpdated = 0;

        foreach ($uploadedFiles as $file) {
            $fileName = $file->getClientOriginalName();

            // 1. Filename validation: Must match the "By Period" pattern, allowing suffixes like (1)
            if (!preg_match('/^\d{5}_([A-Z]{4})([A-Z0-9]{10})\s*\(?\d*\)?\.xlsx?$/i', $fileName, $matches)) {
                $failedFiles[] = "{$fileName} (รูปแบบชื่อไฟล์ไม่ถูกต้องตามเงื่อนไข By Period)";
                continue;
            }

            $dmisGroup = strtoupper($matches[1]);
            $roundNo = $matches[2];

            try {
                $spreadsheet = IOFactory::load($file->getRealPath());
                
                // Find sheet by index or name
                $sheet = null;
                foreach ($spreadsheet->getAllSheets() as $sh) {
                    $title = strtolower($sh->getTitle());
                    if (strpos($title, 'individual') !== false) {
                        $sheet = $sh;
                        break;
                    }
                }
                // Fallback to second sheet (index 1) if not matched by name
                if (!$sheet && isset($spreadsheet->getAllSheets()[1])) {
                    $sheet = $spreadsheet->setActiveSheetIndex(1);
                }
                // Ultimate fallback to active sheet
                if (!$sheet) {
                    $sheet = $spreadsheet->getActiveSheet();
                }

                // Parse header columns at Row 6 and Row 7
                $colMap = [];
                $highestRow = $sheet->getHighestRow();
                
                // Read Row 6
                if ($highestRow >= 6) {
                    $row6 = $sheet->getRowIterator(6)->current();
                    $cellIterator6 = $row6->getCellIterator();
                    $cellIterator6->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator6 as $cell) {
                        $val = trim($cell->getValue() ?? '');
                        if (!empty($val)) {
                            $colMap[$val] = $cell->getColumn();
                        }
                    }
                }

                // Read Row 7 (Subheaders override/supplement)
                if ($highestRow >= 7) {
                    $row7 = $sheet->getRowIterator(7)->current();
                    $cellIterator7 = $row7->getCellIterator();
                    $cellIterator7->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator7 as $cell) {
                        $val = trim($cell->getValue() ?? '');
                        if (!empty($val)) {
                            $colMap[$val] = $cell->getColumn();
                        }
                    }
                }

                $getCol = function($potentialNames) use ($colMap) {
                    foreach ($potentialNames as $name) {
                        if (isset($colMap[$name])) {
                            return $colMap[$name];
                        }
                    }
                    return null;
                };

                $cols = [
                    'trans_id' => $getCol(['Trans ID', 'TRAN_ID', 'เลขที่ธุรกรรม']),
                    'repno' => $getCol(['REP No.', 'REP NO', 'เลขที่ REP']),
                    'hn' => $getCol(['HN', 'Hn', 'รหัส HN']),
                    'an' => $getCol(['AN', 'An', 'รหัส AN']),
                    'cid' => $getCol(['VCTID,NAPNumber,PID', 'PID', 'เลขประจำตัวประชาชน', 'CID', 'บัตรประชาชน', 'เลขบัตรประชาชน', 'เลขประจำตัวบัตรประชาชน']),
                    'ptname' => $getCol(['ชื่อ-สกุล', 'ชื่อ-นามสกุล', 'ชื่อผู้ป่วย', 'ชื่อ', 'ชื่อ นามสกุล', 'ชื่อ - นามสกุล']),
                    'send_date' => $getCol(['วันที่ส่งข้อมูล', 'วันที่ส่ง', 'วันที่ส่งออก']),
                    'vstdate' => $getCol(['วันที่รับบริการ', 'วันที่เข้ารักษา', 'วันที่รับบริการ/วันที่เข้ารักษา', 'วันรับบริการ']),
                    'claim_type_name' => $getCol(['รายการประเภทที่ขอเบิก', 'รายการประเภทที่ขอ', 'ประเภทบริการ']),
                    'qty' => $getCol(['จำนวน', 'จำนวนครั้ง']),
                    'price_unit' => $getCol(['ราคาต่อหน่วย', 'อัตราจ่าย']),
                    'price_ceiling' => $getCol(['ราคาเพดาน', 'เพดานจ่าย']),
                    'claim_price' => $getCol(['รวมเงินที่ขอเบิก', 'ยอดขอเบิก', 'เงินขอเบิก', 'รวมเงินที่ขอ']),
                    'ps_code' => $getCol(['PS CODE', 'PS_CODE', 'รหัสชดเชย']),
                    'pay_percent' => $getCol(['%', 'ร้อยละการจ่าย']),
                    'receive_total' => $getCol(['ชดเชย', 'ยอดชดเชย', 'เงินชดเชย', 'จ่ายจริง', 'รวมเงินชดเชย', 'รวมเงินที่จ่ายชดเชย']),
                    'deny_code' => $getCol(['หมายเหตุ', 'Deny code', 'รหัสปฏิเสธ', 'DENY CODE']),
                    'deny_warning' => $getCol(['หมายเหตุอื่นๆ', 'คำอธิบาย', 'สาเหตุ', 'รายละเอียด']),
                    'hospcode' => $getCol(['HMAIN_OP', 'HMAIN', 'HOSPCODE', 'รหัสหน่วยบริการ']),
                    'pttype_name' => $getCol(['สิทธิการรักษาพยาบาล', 'สิทธิการรักษา', 'สิทธิ', 'สิทธิการรักษาพยาบาล'])
                ];

                // Validate that we found at least the Trans ID column
                if (empty($cols['trans_id'])) {
                    $failedFiles[] = "{$fileName} (ไม่พบโครงสร้างคอลัมน์ Trans ID ในแถวที่ 6 หรือ 7)";
                    continue;
                }

                $rowLimit = $sheet->getHighestDataRow();
                $inserted = 0;
                $updated = 0;

                for ($row = 9; $row <= $rowLimit; $row++) {
                    $transId = $cols['trans_id'] ? trim($sheet->getCell($cols['trans_id'] . $row)->getValue() ?? '') : '';
                    $repNoVal = $cols['repno'] ? trim($sheet->getCell($cols['repno'] . $row)->getValue() ?? '') : '';

                    // Stop row parsing if both Trans ID and REP No are empty
                    if (empty($transId) && empty($repNoVal)) {
                        break;
                    }

                    if (empty($transId)) {
                        continue;
                    }

                    $claimPrice = $cols['claim_price'] ? floatval(str_replace(',', '', $sheet->getCell($cols['claim_price'] . $row)->getValue() ?? 0)) : 0.00;
                    $payPct = $cols['pay_percent'] ? floatval(str_replace(',', '', $sheet->getCell($cols['pay_percent'] . $row)->getValue() ?? 0)) : 0.00;
                    
                    $receiveTotalVal = 0.00;
                    if ($cols['receive_total'] && !is_null($sheet->getCell($cols['receive_total'] . $row)->getValue())) {
                        $receiveTotalVal = floatval(str_replace(',', '', $sheet->getCell($cols['receive_total'] . $row)->getValue()));
                    } else {
                        $receiveTotalVal = $payPct > 0 ? ($claimPrice * ($payPct / 100)) : $claimPrice;
                    }

                    $data = [
                        'hospcode' => $cols['hospcode'] ? trim($sheet->getCell($cols['hospcode'] . $row)->getValue() ?? '') : null,
                        'pttype_name' => $cols['pttype_name'] ? trim($sheet->getCell($cols['pttype_name'] . $row)->getValue() ?? '') : null,
                        'repno' => $repNoVal ?: null,
                        'hn' => $cols['hn'] ? trim($sheet->getCell($cols['hn'] . $row)->getValue() ?? '') : null,
                        'an' => $cols['an'] ? trim($sheet->getCell($cols['an'] . $row)->getValue() ?? '') : null,
                        'cid' => $cols['cid'] ? trim($sheet->getCell($cols['cid'] . $row)->getValue() ?? '') : null,
                        'ptname' => $cols['ptname'] ? trim($sheet->getCell($cols['ptname'] . $row)->getValue() ?? '') : null,
                        'send_date' => $cols['send_date'] ? $this->parseThaiDate($sheet->getCell($cols['send_date'] . $row)->getValue()) : null,
                        'vstdate' => $cols['vstdate'] ? $this->parseThaiDate($sheet->getCell($cols['vstdate'] . $row)->getValue()) : null,
                        'claim_type_name' => $cols['claim_type_name'] ? trim($sheet->getCell($cols['claim_type_name'] . $row)->getValue() ?? '') : null,
                        'qty' => $cols['qty'] ? floatval($sheet->getCell($cols['qty'] . $row)->getValue()) : null,
                        'price_unit' => $cols['price_unit'] ? floatval(str_replace(',', '', $sheet->getCell($cols['price_unit'] . $row)->getValue() ?? 0)) : null,
                        'price_ceiling' => $cols['price_ceiling'] ? floatval(str_replace(',', '', $sheet->getCell($cols['price_ceiling'] . $row)->getValue() ?? 0)) : null,
                        'claim_price' => $claimPrice,
                        'ps_code' => $cols['ps_code'] ? trim($sheet->getCell($cols['ps_code'] . $row)->getValue() ?? '') : null,
                        'pay_percent' => $payPct,
                        'receive_total' => $receiveTotalVal,
                        'deny_code' => $cols['deny_code'] ? trim($sheet->getCell($cols['deny_code'] . $row)->getValue() ?? '') : null,
                        'deny_warning' => $cols['deny_warning'] ? trim($sheet->getCell($cols['deny_warning'] . $row)->getValue() ?? '') : null,
                        'dmis_group' => $dmisGroup,
                        'excel_filename' => $fileName,
                        'round_no' => $roundNo,
                        'updated_at' => now()
                    ];

                    $exists = DB::table('stm_seamless_dmis')->where('trans_id', $transId)->exists();
                    if ($exists) {
                        DB::table('stm_seamless_dmis')->where('trans_id', $transId)->update($data);
                        $updated++;
                    } else {
                        $data['trans_id'] = $transId;
                        $data['created_at'] = now();
                        DB::table('stm_seamless_dmis')->insert($data);
                        $inserted++;
                    }
                }

                $successFiles[] = "{$fileName} (เพิ่มใหม่ {$inserted} รายการ, อัปเดต {$updated} รายการ)";
                $totalInserted += $inserted;
                $totalUpdated += $updated;

            } catch (\Exception $e) {
                $failedFiles[] = "{$fileName} (เกิดข้อผิดพลาด: {$e->getMessage()})";
            }
        }

        $msg = "";
        if (!empty($successFiles)) {
            $msg .= "<b>นำเข้าสำเร็จ " . count($successFiles) . " ไฟล์:</b><br><ul class='text-start small'>" . implode('', array_map(fn($f) => "<li>{$f}</li>", $successFiles)) . "</ul>";
        }
        if (!empty($failedFiles)) {
            $msg .= "<b>ล้มเหลว " . count($failedFiles) . " ไฟล์:</b><br><ul class='text-start small text-danger'>" . implode('', array_map(fn($f) => "<li>{$f}</li>", $failedFiles)) . "</ul>";
            return redirect()->back()->with('error', $msg);
        }

        return redirect()->back()->with('stm_success', $msg);
    }

    public function updateReceipt(Request $request)
    {
        $request->validate([
            'round_no' => 'required',
            'receive_no' => 'required|max:20',
            'receipt_date' => 'required|date',
        ]);

        if (Schema::hasTable('stm_seamless_dmis')) {
            DB::table('stm_seamless_dmis')
                ->where('round_no', $request->round_no)
                ->update([
                    'receive_no' => $request->receive_no,
                    'receipt_date' => $request->receipt_date,
                    'receipt_by' => auth()->user()->name ?? 'system',
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no' => $request->round_no,
            'receive_no' => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }

    protected function parseThaiDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $value = trim($value);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = intval($matches[3]);
            if ($year > 2400) {
                $year -= 543;
            }
            return "{$year}-{$month}-{$day}";
        }
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (is_numeric($value)) {
            try {
                $unixTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value);
                return date('Y-m-d', $unixTimestamp);
            } catch (\Exception $e) {
                // Ignore
            }
        }
        
        return null;
    }
}
