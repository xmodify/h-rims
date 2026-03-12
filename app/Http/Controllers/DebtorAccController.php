<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MainSetting;
use App\Models\BudgetYear;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DebtorAccController extends Controller
{
    public function index(Request $request)
    {
        $hospital_name = MainSetting::where('name', 'hospital_name')->value('value') ?? 'Unknown Hospital';
        $hospital_code = MainSetting::where('name', 'hospital_code')->value('value') ?? '00000';
        
        $budget_year_select = BudgetYear::orderBy('LEAVE_YEAR_ID', 'DESC')->get();
        
        $current_year = date('Y') + 543;
        $current_month = date('n');
        
        if ($current_month >= 10) {
            $default_budget_year = $current_year + 1;
        } else {
            $default_budget_year = $current_year;
        }
        
        $budget_year = $request->budget_year ?? $default_budget_year;
        
        // month_no: 1=Oct, 2=Nov, ..., 12=Sep
        $curr_month_no = ($current_month >= 10) ? $current_month - 9 : $current_month + 3;
        
        return view('debtor.acc_debtor_ledger', compact(
            'hospital_name', 
            'hospital_code', 
            'budget_year_select', 
            'budget_year',
            'curr_month_no'
        ));
    }

    public function export_pdf(Request $request)
    {
        $budget_year = $request->budget_year;
        $month_no = $request->month_no;

        $hospital_name = MainSetting::where('name', 'hospital_name')->value('value') ?? 'Unknown Hospital';
        $hospital_code = MainSetting::where('name', 'hospital_code')->value('value') ?? '00000';

        $data = DB::table('debtor_acc_ledger')
            ->where('budget_year', $budget_year)
            ->where('month_no', $month_no)
            ->get();

        $months_name = ["", "ตุลาคม", "พฤศจิกายน", "ธันวาคม", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน"];
        $month_name = $months_name[$month_no] ?? '';

        $pdf = PDF::loadView('debtor.acc_debtor_ledger_pdf', compact(
            'hospital_name',
            'hospital_code',
            'budget_year',
            'month_no',
            'month_name',
            'data'
        ));

        // Set paper size to A4 landscape
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream("debtor_ledger_{$budget_year}_{$month_no}.pdf");
    }

    public function export_excel(Request $request)
    {
        $budget_year = $request->budget_year;
        $month_no = $request->month_no;

        $hospital_name = MainSetting::where('name', 'hospital_name')->value('value') ?? 'Hospital';
        $data = DB::table('debtor_acc_ledger')
            ->where('budget_year', $budget_year)
            ->where('month_no', $month_no)
            ->get();

        $months_name = ["", "ตุลาคม", "พฤศจิกายน", "ธันวาคม", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน"];
        $month_name = $months_name[$month_no] ?? '';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'ทะเบียนคุมลูกหนี้ค่ารักษาพยาบาล');
        $sheet->setCellValue('A2', "โรงพยาบาล: $hospital_name | ประจำเดือน: $month_name ปีงบประมาณ: $budget_year");
        
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Table Headers
        $headers = ['รหัสบัญชี', 'ชื่อผังบัญชี', 'ยอดยกมา', 'ตั้งหนี้', 'ล้างหนี้/รับ', 'ปรับลด', 'ปรับเพิ่ม', 'คงเหลือยกไป'];
        $cols = range('A', 'H');
        foreach ($headers as $index => $header) {
            $sheet->setCellValue($cols[$index] . '4', $header);
            $sheet->getStyle($cols[$index] . '4')->getFont()->setBold(true);
            $sheet->getStyle($cols[$index] . '4')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Data
        $row_num = 5;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row_num, $item->acc_code);
            $sheet->setCellValue('B' . $row_num, $item->acc_name);
            $sheet->setCellValue('C' . $row_num, $item->balance_old);
            $sheet->setCellValue('D' . $row_num, $item->debt_new);
            $sheet->setCellValue('E' . $row_num, $item->debt_receive);
            $sheet->setCellValue('F' . $row_num, $item->debt_adj_dec);
            $sheet->setCellValue('G' . $row_num, $item->debt_adj_inc);
            $sheet->setCellValue('H' . $row_num, $item->balance_total);
            
            // Format numbers
            $sheet->getStyle('C'.$row_num.':H'.$row_num)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A'.$row_num.':H'.$row_num)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $row_num++;
        }

        // Summary Row
        $sheet->setCellValue('A' . $row_num, 'รวมทั้งหมด');
        $sheet->mergeCells("A$row_num:B$row_num");
        $sheet->getStyle("A$row_num")->getFont()->setBold(true);
        
        $last_data_row = $row_num - 1;
        foreach (range('C', 'H') as $col) {
            $sheet->setCellValue($col . $row_num, "=SUM({$col}5:{$col}{$last_data_row})");
            $sheet->getStyle($col . $row_num)->getFont()->setBold(true);
            $sheet->getStyle($col . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle("A$row_num:H$row_num")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = "debtor_ledger_{$budget_year}_{$month_no}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.urlencode($filename).'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function get_data(Request $request)
    {
        $budget_year = $request->budget_year;
        $month_no = $request->month_no;

        $data = DB::table('debtor_acc_ledger')
            ->where('budget_year', $budget_year)
            ->where('month_no', $month_no)
            ->get();

        return response()->json($data);
    }

    public function save_adjustment(Request $request)
    {
        $where = [
            'budget_year' => $request->budget_year,
            'month_no' => $request->month_no,
            'acc_code' => $request->acc_code,
        ];
        
        $data = [
            'balance_old' => $request->balance_old ?? 0,
            'debt_receive' => $request->debt_receive ?? 0,
            'debt_adj_dec' => $request->debt_adj_dec ?? 0,
            'debt_adj_inc' => $request->debt_adj_inc ?? 0,
            'adj_note' => $request->adj_note,
        ];

        DB::table('debtor_acc_ledger')->where($where)->update($data);

        // Recalculate balance_total
        $row = DB::table('debtor_acc_ledger')->where($where)->first();
        $balance_total = $row->balance_old + $row->debt_new + $row->debt_adj_inc - $row->debt_receive - $row->debt_adj_dec;
        
        DB::table('debtor_acc_ledger')->where($where)->update(['balance_total' => $balance_total]);

        return response()->json(['status' => 'success']);
    }

    private $accounts_map = [
        // OPD
        '1102050101.103' => ['name' => 'ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ', 'table' => 'debtor_1102050101_103', 'date_field' => 'vstdate'],
        '1102050101.109' => ['name' => 'ลูกหนี้-ระบบปฏิบัติการฉุกเฉิน', 'table' => 'debtor_1102050101_109', 'date_field' => 'vstdate'],
        '1102050101.201' => ['name' => 'ลูกหนี้ค่ารักษา UC-OP ใน CUP', 'table' => 'debtor_1102050101_201', 'date_field' => 'vstdate'],
        '1102050101.203' => ['name' => 'ลูกหนี้ค่ารักษา UC-OP นอก CUP (ในจังหวัด)', 'table' => 'debtor_1102050101_203', 'date_field' => 'vstdate'],
        '1102050101.209' => ['name' => 'ลูกหนี้ค่ารักษา P&P', 'table' => 'debtor_1102050101_209', 'date_field' => 'vstdate'],
        '1102050101.216' => ['name' => 'ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)', 'table' => 'debtor_1102050101_216', 'date_field' => 'vstdate'],
        '1102050101.301' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย', 'table' => 'debtor_1102050101_301', 'date_field' => 'vstdate'],
        '1102050101.303' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย', 'table' => 'debtor_1102050101_303', 'date_field' => 'vstdate'],
        '1102050101.307' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม-กองทุนทดแทน', 'table' => 'debtor_1102050101_307', 'date_field' => 'COALESCE(dchdate, vstdate)'],
        '1102050101.309' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม-ค่าใช้จ่ายสูง OP', 'table' => 'debtor_1102050101_309', 'date_field' => 'vstdate'],
        '1102050101.401' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง OP', 'table' => 'debtor_1102050101_401', 'date_field' => 'vstdate'],
        '1102050101.501' => ['name' => 'ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP', 'table' => 'debtor_1102050101_501', 'date_field' => 'vstdate'],
        '1102050101.503' => ['name' => 'ลูกหนี้ค่ารักษา ต่างด้าว นอก CUP OP', 'table' => 'debtor_1102050101_503', 'date_field' => 'vstdate'],
        '1102050101.701' => ['name' => 'ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะ OP ใน CUP', 'table' => 'debtor_1102050101_701', 'date_field' => 'vstdate'],
        '1102050101.702' => ['name' => 'ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะ OP นอก CUP', 'table' => 'debtor_1102050101_702', 'date_field' => 'vstdate'],
        '1102050102.106' => ['name' => 'ลูกหนี้ค่ารักษา ชําระเงิน OP', 'table' => 'debtor_1102050102_106', 'date_field' => 'vstdate'],
        '1102050102.108' => ['name' => 'ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP', 'table' => 'debtor_1102050102_108', 'date_field' => 'vstdate'],
        '1102050102.110' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP', 'table' => 'debtor_1102050102_110', 'date_field' => 'vstdate'],
        '1102050102.602' => ['name' => 'ลูกหนี้ค่ารักษา พรบ.รถ OP', 'table' => 'debtor_1102050102_602', 'date_field' => 'vstdate'],
        '1102050102.801' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท. OP', 'table' => 'debtor_1102050102_801', 'date_field' => 'vstdate'],
        '1102050102.803' => ['name' => 'ลูกหนี้ค่ารักษา อปท.รูปแบบพิเศษ OP', 'table' => 'debtor_1102050102_803', 'date_field' => 'vstdate'],
        
        // IPD
        '1102050101.202' => ['name' => 'ลูกหนี้ค่ารักษา UC-IP', 'table' => 'debtor_1102050101_202', 'date_field' => 'dchdate'],
        '1102050101.217' => ['name' => 'ลูกหนี้ค่ารักษา UC-IP บริการเฉพาะ (CR)', 'table' => 'debtor_1102050101_217', 'date_field' => 'dchdate'],
        '1102050101.302' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม IP เครือข่าย', 'table' => 'debtor_1102050101_302', 'date_field' => 'dchdate'],
        '1102050101.304' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย', 'table' => 'debtor_1102050101_304', 'date_field' => 'dchdate'],
        '1102050101.308' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม 72 ชม.', 'table' => 'debtor_1102050101_308', 'date_field' => 'dchdate'],
        '1102050101.310' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม ค่าใช้จ่ายสูง IP', 'table' => 'debtor_1102050101_310', 'date_field' => 'dchdate'],
        '1102050101.402' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง IP', 'table' => 'debtor_1102050101_402', 'date_field' => 'dchdate'],
        '1102050101.502' => ['name' => 'ลูกหนี้ค่ารักษา ต่างด้าว IP', 'table' => 'debtor_1102050101_502', 'date_field' => 'dchdate'],
        '1102050101.504' => ['name' => 'ลูกหนี้ค่ารักษา ต่างด้าว นอก CUP IP', 'table' => 'debtor_1102050101_504', 'date_field' => 'dchdate'],
        '1102050101.704' => ['name' => 'ลูกหนี้ค่ารักษา ต่างด้าว เบิกส่วนกลาง IP', 'table' => 'debtor_1102050101_704', 'date_field' => 'dchdate'],
        '1102050102.107' => ['name' => 'ลูกหนี้ค่ารักษา ชำระเงิน IP', 'table' => 'debtor_1102050102_107', 'date_field' => 'dchdate'],
        '1102050102.109' => ['name' => 'ลูกหนี้ค่ารักษา เบิกต้นสังกัด IP', 'table' => 'debtor_1102050102_109', 'date_field' => 'dchdate'],
        '1102050102.111' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น IP', 'table' => 'debtor_1102050102_111', 'date_field' => 'dchdate'],
        '1102050102.603' => ['name' => 'ลูกหนี้ค่ารักษา พรบ.รถ IP', 'table' => 'debtor_1102050102_603', 'date_field' => 'dchdate'],
        '1102050102.802' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท. IP', 'table' => 'debtor_1102050102_802', 'date_field' => 'dchdate'],
        '1102050102.804' => ['name' => 'ลูกหนี้ค่ารักษา อปท.รูปแบบพิเศษ IP', 'table' => 'debtor_1102050102_804', 'date_field' => 'dchdate'],
    ];

    public function init_month_rows(Request $request)
    {
        $budget_year = $request->budget_year;
        $month_no = $request->month_no;

        // VST Month calculation
        if ($month_no <= 3) { $m = $month_no + 9; $y = $budget_year - 544; } else { $m = $month_no - 3; $y = $budget_year - 543; }
        $vst_month = sprintf("%04d-%02d", $y, $m);

        foreach ($this->accounts_map as $acc_code => $info) {
            // หา Balance Old
            $balance_old = 0;
            if ($month_no > 1) {
                $prev = DB::table('debtor_acc_ledger')
                    ->where('budget_year', $budget_year)
                    ->where('month_no', $month_no - 1)
                    ->where('acc_code', $acc_code)
                    ->first();
                $balance_old = $prev->balance_total ?? 0;
            }

            DB::table('debtor_acc_ledger')->updateOrInsert(
                ['budget_year' => $budget_year, 'month_no' => $month_no, 'acc_code' => $acc_code],
                [
                    'vst_month' => $vst_month,
                    'acc_name' => $info['name'],
                    'balance_old' => $balance_old,
                    'updated_at' => now(),
                ]
            );

            // คำนวณยอดรวมใหม่
            $row = DB::table('debtor_acc_ledger')
                ->where(['budget_year' => $budget_year, 'month_no' => $month_no, 'acc_code' => $acc_code])
                ->first();
            
            $balance_total = $row->balance_old + $row->debt_new + $row->debt_adj_inc - $row->debt_receive - $row->debt_adj_dec;
            DB::table('debtor_acc_ledger')
                ->where(['budget_year' => $budget_year, 'month_no' => $month_no, 'acc_code' => $acc_code])
                ->update(['balance_total' => $balance_total]);
        }
        return response()->json(['status' => 'success']);
    }

    public function process_ledger(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $budget_year = $request->budget_year;
        $selected_month_no = $request->month_no;

        $month_range = $selected_month_no ? [$selected_month_no] : range(1, 12);
        $fiscal_start_date = ($budget_year - 544) . "-10-01";
        $fiscal_end_date = ($budget_year - 543) . "-09-30";

        $existing_ledger = DB::table('debtor_acc_ledger')
            ->where('budget_year', $budget_year)
            ->get()
            ->groupBy('acc_code')
            ->map(function ($items) {
                return $items->keyBy('month_no');
            });

        $processed_count = 0;

        foreach ($this->accounts_map as $acc_code => $info) {
            $tableName = $info['table'];
            $dateField = $info['date_field'];
            
            // 1. Debt New calculation (Always based on Visit/Dch Date)
            $query_new = DB::table($tableName)
                ->select(
                    DB::raw("YEAR($dateField) as y, MONTH($dateField) as m"),
                    DB::raw("SUM(debtor) as total")
                );
            
            if (strpos($dateField, '(') !== false) {
                $query_new->whereRaw("$dateField BETWEEN ? AND ?", [$fiscal_start_date, $fiscal_end_date]);
            } else {
                $query_new->whereBetween($dateField, [$fiscal_start_date, $fiscal_end_date]);
            }
            
            $debt_new_rows = $query_new->groupBy('y', 'm')->get();
            
            $new_map = [];
            foreach ($debt_new_rows as $row) {
                $m_no = ($row->m >= 10) ? $row->m - 9 : $row->m + 3;
                $new_map[intval($m_no)] = $row->total;
            }

            // 2. Debt Receive calculation (BASED ON RECEIPT DATE as requested)
            $receive_map = [];
            
            if ($acc_code == '1102050101.216') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT s.receipt_date as r_date, (s.receive_total - s.receive_pp) as total
                        FROM stm_ucs s JOIN debtor_1102050101_216 d ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5)
                        WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT sk.receipt_date as r_date, sk.receive_total as total
                        FROM stm_ucs_kidney sk JOIN debtor_1102050101_216 d ON sk.cid = d.cid AND sk.datetimeadm = d.vstdate
                        WHERE sk.receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050101.217') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT s.receipt_date as r_date, (s.receive_total - s.receive_ip_compensate_pay) as total
                        FROM stm_ucs s JOIN debtor_1102050101_217 d ON s.an = d.an
                        WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT sk.receipt_date as r_date, sk.receive_total as total
                        FROM stm_ucs_kidney sk JOIN debtor_1102050101_217 d ON sk.cid = d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate
                        WHERE sk.receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050101.202') {
                $rec_rows = DB::select("
                    SELECT YEAR(s.receipt_date) as y, MONTH(s.receipt_date) as m, SUM(s.receive_ip_compensate_pay) as total
                    FROM stm_ucs s JOIN debtor_1102050101_202 d ON s.an = d.an
                    WHERE s.receipt_date BETWEEN ? AND ?
                    GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if (in_array($acc_code, ['1102050101.401', '1102050102.110', '1102050102.803'])) {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_ofc s JOIN $tableName d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, amount as total FROM stm_ofc_csop s JOIN $tableName d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if (in_array($acc_code, ['1102050101.402', '1102050102.111', '1102050102.804'])) {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_ofc s JOIN $tableName d ON s.an = d.an WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, gtotal as total FROM stm_ofc_cipn s JOIN $tableName d ON s.an = d.an WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.801') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, compensate_treatment as total FROM stm_lgo s JOIN debtor_1102050102_801 d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, compensate_kidney as total FROM stm_lgo_kidney s JOIN debtor_1102050102_801 d ON s.hn = d.hn AND s.datetimeadm = d.vstdate WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.802') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, compensate_treatment as total FROM stm_lgo s JOIN debtor_1102050102_802 d ON s.an = d.an WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, compensate_kidney as total FROM stm_lgo_kidney s JOIN debtor_1102050102_802 d ON s.cid = d.cid AND s.datetimeadm BETWEEN d.regdate AND d.dchdate WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.106') {
                $rec_rows = DB::connection('hosxp')->select("
                    SELECT YEAR(r.bill_date) as y, MONTH(r.bill_date) as m, SUM(r.bill_amount) as total
                    FROM rcpt_print r JOIN hrims.debtor_1102050102_106 d ON r.vn = d.vn
                    WHERE r.bill_date BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.107') {
                $rec_rows = DB::connection('hosxp')->select("
                    SELECT YEAR(r.bill_date) as y, MONTH(r.bill_date) as m, SUM(r.bill_amount) as total
                    FROM rcpt_print r JOIN hrims.debtor_1102050102_107 d ON r.vn = d.an
                    WHERE r.bill_date BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050101.309') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT s.receipt_date as r_date, (IFNULL(s.amount,0)+ IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS total 
                        FROM stm_sss_kidney s JOIN debtor_1102050101_309 d ON s.cid = d.cid AND s.vstdate = d.vstdate
                        WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receive_date as r_date, receive as total FROM debtor_1102050101_309 
                        WHERE receive_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else {
                // Default logic: Group by receive_date if available
                $hasRecDate = \Illuminate\Support\Facades\Schema::hasColumn($tableName, 'receive_date');
                if ($hasRecDate) {
                    $rec_rows = DB::table($tableName)
                        ->select(DB::raw("YEAR(receive_date) as y, MONTH(receive_date) as m, SUM(receive) as total"))
                        ->whereBetween('receive_date', [$fiscal_start_date, $fiscal_end_date])
                        ->groupBy('y', 'm')
                        ->get();
                    foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;
                }
            }

            foreach ($month_range as $month_no) {
                $debt_new = $new_map[$month_no] ?? 0;
                $debt_receive = $receive_map[$month_no] ?? 0;

                if ($month_no <= 3) { $m = $month_no + 9; $y = $budget_year - 544; } else { $m = $month_no - 3; $y = $budget_year - 543; }
                $vst_month = sprintf("%04d-%02d", $y, $m);

                $current_balance_old = 0;
                if ($month_no > 1) {
                    $prev = DB::table('debtor_acc_ledger')
                        ->where('budget_year', $budget_year)
                        ->where('month_no', $month_no - 1)
                        ->where('acc_code', $acc_code)
                        ->first();
                    $current_balance_old = $prev->balance_total ?? 0;
                } else if (isset($existing_ledger[$acc_code][1])) {
                    $current_balance_old = $existing_ledger[$acc_code][1]->balance_old;
                }

                $adj_dec = 0; $adj_inc = 0; $adj_note = null;
                if (isset($existing_ledger[$acc_code][$month_no])) {
                    $row = $existing_ledger[$acc_code][$month_no];
                    $adj_dec = $row->debt_adj_dec;
                    $adj_inc = $row->debt_adj_inc;
                    $adj_note = $row->adj_note;
                }

                $balance_total = $current_balance_old + $debt_new + $adj_inc - $debt_receive - $adj_dec;

                DB::table('debtor_acc_ledger')->updateOrInsert(
                    ['budget_year' => $budget_year, 'month_no' => $month_no, 'acc_code' => $acc_code],
                    [
                        'vst_month' => $vst_month,
                        'acc_name' => $info['name'],
                        'balance_old' => $current_balance_old,
                        'debt_new' => $debt_new,
                        'debt_receive' => $debt_receive,
                        'debt_adj_dec' => $adj_dec,
                        'debt_adj_inc' => $adj_inc,
                        'adj_note' => $adj_note,
                        'balance_total' => $balance_total,
                        'updated_at' => now(),
                    ]
                );
                $processed_count++;
            }
        }
        return response()->json(['status' => 'success', 'count' => $processed_count]);
    }
}
