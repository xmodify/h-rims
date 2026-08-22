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
        
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Table Headers (Row 4)
        $sheet->setCellValue('A4', 'รหัสบัญชี');
        $sheet->setCellValue('B4', 'ชื่อผังบัญชี');
        $sheet->setCellValue('C4', 'ยอดยกมา');
        $sheet->setCellValue('E4', 'รับจ่ายในรอบเดือน');
        $sheet->setCellValue('I4', 'ยอดคงเหลือยกไป');
        $sheet->setCellValue('K4', '≤ 90 วัน');
        $sheet->setCellValue('L4', '91-365 วัน');
        $sheet->setCellValue('M4', '> 365 วัน');

        // Merges for double-header
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:D4');
        $sheet->mergeCells('E4:H4');
        $sheet->mergeCells('I4:J4');
        $sheet->mergeCells('K4:K5');
        $sheet->mergeCells('L4:L5');
        $sheet->mergeCells('M4:M5');

        // Sub-headers (Row 5)
        $sheet->setCellValue('C5', 'เดบิต');
        $sheet->setCellValue('D5', 'เครดิต');
        $sheet->setCellValue('E5', 'ตั้งหนี้ (เดบิต)');
        $sheet->setCellValue('F5', 'รับชำระ (เครดิต)');
        $sheet->setCellValue('G5', 'ปรับเพิ่ม (เดบิต)');
        $sheet->setCellValue('H5', 'ปรับลด (เครดิต)');
        $sheet->setCellValue('I5', 'เดบิต');
        $sheet->setCellValue('J5', 'เครดิต');

        // Header Styling
        $header_style = $sheet->getStyle('A4:M5');
        $header_style->getFont()->setBold(true);
        $header_style->getAlignment()->setHorizontal('center')->setVertical('center');
        $header_style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Data Rows
        $row_num = 6;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row_num, $item->acc_code);
            $sheet->setCellValue('B' . $row_num, $item->acc_name);
            
            $old_val = floatval($item->balance_old);
            $sheet->setCellValue('C' . $row_num, $old_val >= 0 ? $old_val : 0);
            $sheet->setCellValue('D' . $row_num, $old_val < 0 ? abs($old_val) : 0);
            
            $sheet->setCellValue('E' . $row_num, floatval($item->debt_new));
            $sheet->setCellValue('F' . $row_num, floatval($item->debt_receive));
            $sheet->setCellValue('G' . $row_num, floatval($item->debt_adj_inc));
            $sheet->setCellValue('H' . $row_num, floatval($item->debt_adj_dec));
            
            $total_val = floatval($item->balance_total);
            $sheet->setCellValue('I' . $row_num, $total_val >= 0 ? $total_val : 0);
            $sheet->setCellValue('J' . $row_num, $total_val < 0 ? abs($total_val) : 0);
            
            $sheet->setCellValue('K' . $row_num, floatval($item->aging_90));
            $sheet->setCellValue('L' . $row_num, floatval($item->aging_365));
            $sheet->setCellValue('M' . $row_num, floatval($item->aging_over));
            
            // Format numbers
            $sheet->getStyle('C'.$row_num.':M'.$row_num)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A'.$row_num.':M'.$row_num)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $row_num++;
        }

        // Summary Row
        $sheet->setCellValue('A' . $row_num, 'รวมทั้งหมด');
        $sheet->mergeCells("A$row_num:B$row_num");
        $sheet->getStyle("A$row_num")->getFont()->setBold(true);
        
        $last_data_row = $row_num - 1;
        foreach (range('C', 'M') as $col) {
            $sheet->setCellValue($col . $row_num, "=SUM({$col}6:{$col}{$last_data_row})");
            $sheet->getStyle($col . $row_num)->getFont()->setBold(true);
            $sheet->getStyle($col . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle("A$row_num:M$row_num")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        foreach (range('A', 'M') as $col) {
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
        $budget_year = intval($request->budget_year);
        $month_no = intval($request->month_no);

        $data = DB::table('debtor_acc_ledger')
            ->where('budget_year', $budget_year)
            ->where('month_no', $month_no)
            ->get();

        // Calculate acc_period in BE (Buddhist Era) format
        if ($month_no <= 3) {
            $m = $month_no + 9;
            $y = $budget_year - 1;
        } else {
            $m = $month_no - 3;
            $y = $budget_year;
        }
        $acc_period = sprintf("%04d-%02d", $y, $m);

        // Fetch corresponding general ledger rows for this period
        $tb_rows = DB::table('hosfin_trial_balance')
            ->where('acc_period', $acc_period)
            ->get()
            ->keyBy('account_code');

        $revenue_map = $this->debtor_revenue_map;

        // Map Trial Balance values to Ledger rows
        $data = $data->map(function ($row) use ($tb_rows, $revenue_map) {
            $code = $row->acc_code;
            $tb = $tb_rows->get($code);

            if ($tb) {
                // Asset account: map all debit/credit fields
                $row->tb_debit_bf = floatval($tb->debit_bf);
                $row->tb_credit_bf = floatval($tb->credit_bf);
                $row->tb_debit_month = floatval($tb->debit_month);
                $row->tb_credit_month = floatval($tb->credit_month);
                $row->tb_debit_net = floatval($tb->debit_net);
                $row->tb_credit_net = floatval($tb->credit_net);
                $row->tb_has_data = true;
            } else {
                $row->tb_debit_bf = 0.00;
                $row->tb_credit_bf = 0.00;
                $row->tb_debit_month = 0.00;
                $row->tb_credit_month = 0.00;
                $row->tb_debit_net = 0.00;
                $row->tb_credit_net = 0.00;
                $row->tb_has_data = false;
            }

            // Map Revenue (Category 4)
            $rev_code = $revenue_map[$code] ?? null;
            $row->tb_rev_code = $rev_code;
            if ($rev_code) {
                $tb_rev = $tb_rows->get($rev_code);
                if ($tb_rev) {
                    // Revenue account: map all debit/credit fields
                    $row->tb_rev_debit_bf = floatval($tb_rev->debit_bf);
                    $row->tb_rev_credit_bf = floatval($tb_rev->credit_bf);
                    $row->tb_rev_debit_month = floatval($tb_rev->debit_month);
                    $row->tb_rev_credit_month = floatval($tb_rev->credit_month);
                    $row->tb_rev_debit_net = floatval($tb_rev->debit_net);
                    $row->tb_rev_credit_net = floatval($tb_rev->credit_net);
                    $row->tb_rev_has_data = true;
                } else {
                    $row->tb_rev_debit_bf = 0.00;
                    $row->tb_rev_credit_bf = 0.00;
                    $row->tb_rev_debit_month = 0.00;
                    $row->tb_rev_credit_month = 0.00;
                    $row->tb_rev_debit_net = 0.00;
                    $row->tb_rev_credit_net = 0.00;
                    $row->tb_rev_has_data = false;
                }
            } else {
                $row->tb_rev_debit_bf = 0.00;
                $row->tb_rev_credit_bf = 0.00;
                $row->tb_rev_debit_month = 0.00;
                $row->tb_rev_credit_month = 0.00;
                $row->tb_rev_debit_net = 0.00;
                $row->tb_rev_credit_net = 0.00;
                $row->tb_rev_has_data = false;
            }

            return $row;
        });

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
        '1102050102.103' => ['name' => 'ลูกหนี้ค่าตรวจสุขภาพ บุคคลภายนอก', 'table' => 'debtor_1102050102_103', 'date_field' => 'vstdate'],
        '1102050102.106' => ['name' => 'ลูกหนี้ค่ารักษา ชําระเงิน OP', 'table' => 'debtor_1102050102_106', 'date_field' => 'vstdate'],
        '1102050102.108' => ['name' => 'ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP', 'table' => 'debtor_1102050102_108', 'date_field' => 'vstdate'],
        '1102050102.110' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP', 'table' => 'debtor_1102050102_110', 'date_field' => 'vstdate'],
        '1102050102.301' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม OP นอกเครือข่าย ต่างสังกัด สป.สธ.', 'table' => 'debtor_1102050102_301', 'date_field' => 'vstdate'],
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
        '1102050102.302' => ['name' => 'ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย ต่างสังกัด สป.สธ.', 'table' => 'debtor_1102050102_302', 'date_field' => 'dchdate'],
        '1102050102.603' => ['name' => 'ลูกหนี้ค่ารักษา พรบ.รถ IP', 'table' => 'debtor_1102050102_603', 'date_field' => 'dchdate'],
        '1102050102.802' => ['name' => 'ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท. IP', 'table' => 'debtor_1102050102_802', 'date_field' => 'dchdate'],
        '1102050102.804' => ['name' => 'ลูกหนี้ค่ารักษา อปท.รูปแบบพิเศษ IP', 'table' => 'debtor_1102050102_804', 'date_field' => 'dchdate'],
    ];

    private $debtor_revenue_map = [
        // OPD
        '1102050101.103' => '4301020102.104', // ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ -> รายได้ค่าตรวจสุขภาพ-หน่วยงานภาครัฐ
        '1102050101.109' => '4301020102.105', // ลูกหนี้-ระบบปฏิบัติการฉุกเฉิน -> รายได้จากระบบปฏิบัติการฉุกเฉิน (EMS)
        '1102050101.201' => '4301020105.201', // ลูกหนี้ค่ารักษา UC-OP ใน CUP -> รายได้ค่ารักษา UC -OP ใน CUP
        '1102050101.203' => '4301020105.203', // ลูกหนี้ค่ารักษา UC-OP นอก CUP (ในจังหวัด) -> รายได้ค่ารักษา UC - OP นอก CUP ในจังหวัด
        '1102050101.209' => '4301020105.241', // ลูกหนี้ค่ารักษา P&P -> รายได้ค่ารักษาด้านการสร้างเสริมสุขภาพและป้องกันโรค (P&P)
        '1102050101.216' => '4301020105.244', // ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) -> รายได้ค่ารักษา UC- OP บริการกรณีเฉพาะ (CR)
        '1102050101.301' => '4301020106.305', // ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย -> รายได้ค่ารักษาประกันสังคม OP-เครือข่าย
        '1102050101.303' => '4301020106.307', // ลูกหนี้ค่ารักษา ประกันสังคม OP-นอกเครือข่าย -> รายได้ค่ารักษาประกันสังคม OP-นอกเครือข่าย
        '1102050101.307' => '4301020106.311', // ลูกหนี้ค่ารักษา ประกันสังคม-กองทุนทดแทน -> รายได้ค่ารักษาประกันสังคม-กองทุนทดแทน
        '1102050101.309' => '4301020106.313', // ลูกหนี้ค่ารักษา ประกันสังคม-ค่าใช้จ่ายสูง OP -> รายได้ค่ารักษาประกันสังคม-ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP
        '1102050101.401' => '4301020104.401', // ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง OP -> รายได้ค่ารักษาเบิกจ่ายตรงกรมบัญชีกลาง OP
        '1102050101.501' => '4301020106.503', // ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP -> รายได้ค่ารักษาแรงงานต่างด้าว OP
        '1102050101.503' => '4301020106.512', // ลูกหนี้ค่ารักษา ต่างด้าว นอก CUP OP -> รายได้ค่ารักษาแรงงานต่างด้าว OP นอก CUP
        '1102050101.701' => '4301020106.709', // ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะ OP ใน CUP -> รายได้ค่ารักษา-บุคคลที่มีปัญหาสถานะและสิทธิ OP ใน CUP
        '1102050101.702' => '4301020106.701', // ลูกหนี้ค่ารักษา บุคคลที่มีปัญหาสถานะ OP นอก CUP -> รายได้ค่ารักษาบุคคลที่มีปัญหาสถานะและสิทธิ OP นอก CUP
        '1102050102.103' => '4202010199.101', // ลูกหนี้ค่าตรวจสุขภาพ บุคคลภายนอก -> รายได้ค่าธรรมเนียมการบริการอื่น
        '1102050102.106' => '4301020104.106', // ลูกหนี้ค่ารักษา ชําระเงิน OP -> รายได้ค่ารักษาชำระเงิน OP
        '1102050102.108' => '4301020104.104', // ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP -> รายได้ค่ารักษาเบิกต้นสังกัด OP
        '1102050102.110' => '4301020104.108', // ลูกหนี้ค่ารักษา เบิกจ่ายตรงหน่วยงานอื่น OP -> รายได้ค่ารักษาเบิกจ่ายตรง-หน่วยงานอื่น - OP
        '1102050102.301' => '4301020106.307', // ลูกหนี้ค่ารักษา ประกันสังคม OP นอกเครือข่าย ต่างสังกัด สป.สธ.
        '1102050102.602' => '4301020104.602', // ลูกหนี้ค่ารักษา พรบ.รถ OP -> รายได้ค่ารักษา พรบ.รถ OP
        '1102050102.801' => '4301020104.801', // ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท. OP -> รายได้ค่ารักษาเบิกจ่ายตรง- อปท. OP
        '1102050102.803' => '4301020104.805', // ลูกหนี้ค่ารักษา อปท.รูปแบบพิเศษ OP -> รายได้ค่ารักษาเบิกจ่ายตรง - อปท.รูปแบบพิเศษ OP

        // IPD
        '1102050101.202' => '4301020105.202', // ลูกหนี้ค่ารักษา UC-IP -> รายได้ค่ารักษา UC-IP
        '1102050101.217' => '4301020105.245', // ลูกหนี้ค่ารักษา UC-IP บริการเฉพาะ (CR) -> รายได้ค่ารักษา UC - IP บริการกรณีเฉพาะ (CR)
        '1102050101.302' => '4301020106.306', // ลูกหนี้ค่ารักษา ประกันสังคม IP เครือข่าย -> รายได้ค่ารักษาประกันสังคม IP-เครือข่าย
        '1102050101.304' => '4301020106.308', // ลูกหนี้ค่ารักษา ประกันสังคม IP นอกเครือข่าย -> รายได้ค่ารักษาประกันสังคม IP-นอกเครือข่าย
        '1102050101.308' => '4301020106.312', // ลูกหนี้ค่ารักษา ประกันสังคม 72 ชม. -> รายได้ค่ารักษาประกันสังคม 72 ชั่วโมงแรก
        '1102050101.310' => '4301020106.314', // ลูกหนี้ค่ารักษา ประกันสังคม ค่าใช้จ่ายสูง IP -> รายได้ค่ารักษาประกันสังคม-ค่าใช้จ่ายสูง IP
        '1102050101.402' => '4301020104.402', // ลูกหนี้ค่ารักษา เบิกจ่ายตรงกรมบัญชีกลาง IP -> รายได้ค่ารักษาเบิกจ่ายตรงกรมบัญชีกลาง IP
        '1102050101.502' => null,
        '1102050101.504' => null,
        '1102050101.704' => '4301020106.710', // ลูกหนี้ค่ารักษา ต่างด้าว เบิกส่วนกลาง IP -> รายได้ค่ารักษาบุคคลที่มีปัญหาสถานะและสิทธิ - เบิกจากส่วนกลาง IP
        '1102050102.107' => '4301020104.107', // ลูกหนี้ค่ารักษา ชำระเงิน IP -> รายได้ค่ารักษาชำระเงิน IP
        '1102050102.109' => '4301020104.105', // ลูกหนี้ค่ารักษา เบิกต้นสังกัด IP -> รายได้ค่ารักษาเบิกต้นสังกัด IP
        '1102050102.111' => null,
        '1102050102.302' => '4301020106.308',
        '1102050102.603' => '4301020104.603', // ลูกหนี้ค่ารักษา พรบ.รถ IP -> รายได้ค่ารักษา พรบ.รถ IP
        '1102050102.802' => '4301020104.802', // ลูกหนี้ค่ารักษา เบิกจ่ายตรง อปท. IP -> รายได้ค่ารักษาเบิกจ่ายตรง-อปท. IP
        '1102050102.804' => '4301020104.806', // ลูกหนี้ค่ารักษา อปท.รูปแบบพิเศษ IP -> รายได้ค่ารักษาเบิกจ่ายตรง- อปท.รูปแบบพิเศษ IP
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

            if (!\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                continue;
            }
            
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

            } else if (in_array($acc_code, ['1102050101.401', '1102050102.803'])) {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_ofc s JOIN $tableName d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, amount as total FROM stm_ofc_csop s JOIN $tableName d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.110') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_bmt s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.vstdate = d.vstdate WHERE s.receipt_date BETWEEN ? AND ? AND SUBSTRING(s.stm_filename, 11) LIKE 'O%' AND d.ofc > 0
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_bmt_kidney s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.datetimeadm = d.vstdate WHERE s.receipt_date BETWEEN ? AND ? AND d.kidney > 0
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_srt s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.vstdate = d.vstdate WHERE s.receipt_date BETWEEN ? AND ? AND d.ofc > 0
                        UNION ALL
                        SELECT receipt_date as r_date, amount as total FROM stm_ofc_csop s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE s.receipt_date BETWEEN ? AND ? AND s.sys <> 'HD' AND d.ofc > 0
                        UNION ALL
                        SELECT receipt_date as r_date, amount as total FROM stm_ofc_csop s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.vstdate = d.vstdate WHERE s.receipt_date BETWEEN ? AND ? AND s.sys = 'HD' AND d.kidney > 0
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_pvt s JOIN debtor_1102050102_110 d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) WHERE s.receipt_date BETWEEN ? AND ? AND d.ofc > 0
                    ) t GROUP BY y, m
                ", [
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date
                ]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if (in_array($acc_code, ['1102050101.402', '1102050102.804'])) {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_ofc s JOIN $tableName d ON s.an = d.an WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, gtotal as total FROM stm_ofc_cipn s JOIN $tableName d ON s.an = d.an WHERE receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date, $fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.111') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, receive_total as total FROM stm_bmt s JOIN debtor_1102050102_111 d ON s.an = d.an WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_bmt_kidney s JOIN debtor_1102050102_111 d ON s.hn = d.hn AND s.datetimeadm BETWEEN d.regdate AND d.dchdate WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_srt s JOIN debtor_1102050102_111 d ON s.an = d.an WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, gtotal as total FROM stm_ofc_cipn s JOIN debtor_1102050102_111 d ON s.an = d.an WHERE s.receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, receive_total as total FROM stm_pvt s JOIN debtor_1102050102_111 d ON s.an = d.an WHERE s.receipt_date BETWEEN ? AND ?
                    ) t GROUP BY y, m
                ", [
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date,
                    $fiscal_start_date, $fiscal_end_date
                ]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.801') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT receipt_date as r_date, compensate_treatment as total FROM stm_lgo s JOIN debtor_1102050102_801 d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5) AND d.lgo > 0 WHERE receipt_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT receipt_date as r_date, compensate_kidney as total FROM stm_lgo_kidney s JOIN debtor_1102050102_801 d ON s.hn = d.hn AND s.datetimeadm = d.vstdate AND d.kidney > 0 WHERE receipt_date BETWEEN ? AND ?
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
                    SELECT YEAR(r.bill_date) as y, MONTH(r.bill_date) as m, SUM(r.total_amount) as total
                    FROM rcpt_print r 
                    JOIN hrims.debtor_1102050102_106 d ON r.vn = d.vn
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                    WHERE r.bill_date BETWEEN ? AND ? 
                    AND a.rcpno IS NULL
                    AND (
                        d.created_at IS NULL 
                        OR CAST(CONCAT(r.bill_date, ' ', COALESCE(NULLIF(TRIM(r.bill_time), ''), '00:00:00')) AS DATETIME) > d.created_at
                    )
                    GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050102.107') {
                $rec_rows = DB::connection('hosxp')->select("
                    SELECT YEAR(r.bill_date) as y, MONTH(r.bill_date) as m, SUM(r.total_amount) as total
                    FROM rcpt_print r 
                    JOIN hrims.debtor_1102050102_107 d ON r.vn = d.an
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                    WHERE r.bill_date BETWEEN ? AND ? 
                    AND a.rcpno IS NULL
                    AND (
                        d.created_at IS NULL 
                        OR CAST(CONCAT(r.bill_date, ' ', COALESCE(NULLIF(TRIM(r.bill_time), ''), '00:00:00')) AS DATETIME) > d.created_at
                    )
                    GROUP BY y, m
                ", [$fiscal_start_date, $fiscal_end_date]);
                foreach($rec_rows as $rr) $receive_map[intval(($rr->m >= 10) ? $rr->m - 9 : $rr->m + 3)] = $rr->total;

            } else if ($acc_code == '1102050101.309') {
                $rec_rows = DB::select("
                    SELECT YEAR(r_date) as y, MONTH(r_date) as m, SUM(total) as total
                    FROM (
                        SELECT s.receipt_date as r_date, (IFNULL(s.amount,0)+ IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS total 
                        FROM stm_sss_kidney s JOIN debtor_1102050101_309 d ON s.cid = d.cid AND s.vstdate = d.vstdate AND s.hreg = s.hcode
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

            // 3. Adjustments calculation (BASED ON adj_date)
            $adj_map = [];
            $hasAdjDate = \Illuminate\Support\Facades\Schema::hasColumn($tableName, 'adj_date');
            if ($hasAdjDate) {
                $adj_rows = DB::table($tableName)
                    ->select(
                        DB::raw("YEAR(adj_date) as y, MONTH(adj_date) as m"),
                        DB::raw("SUM(adj_inc) as inc_total, SUM(adj_dec) as dec_total"),
                        DB::raw("GROUP_CONCAT(DISTINCT adj_note SEPARATOR ', ') as notes")
                    )
                    ->whereBetween('adj_date', [$fiscal_start_date, $fiscal_end_date])
                    ->groupBy('y', 'm')
                    ->get();
                foreach($adj_rows as $row) {
                    $m_no = ($row->m >= 10) ? $row->m - 9 : $row->m + 3;
                    $adj_map[intval($m_no)] = ['inc' => $row->inc_total, 'dec' => $row->dec_total, 'notes' => $row->notes];
                }
            }

            foreach ($month_range as $month_no) {
                $debt_new = $new_map[$month_no] ?? 0;
                $debt_receive = $receive_map[$month_no] ?? 0;

                if ($month_no <= 3) { $m = $month_no + 9; $y = $budget_year - 544; } else { $m = $month_no - 3; $y = $budget_year - 543; }
                $vst_month = sprintf("%04d-%02d", $y, $m);
                $month_end = date('Y-m-t', strtotime("$y-$m-01"));

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

                // Adjustments from Source Table
                $adj_dec = $adj_map[$month_no]['dec'] ?? 0;
                $adj_inc = $adj_map[$month_no]['inc'] ?? 0;
                $adj_note = $adj_map[$month_no]['notes'] ?? null;

                // If no adjustments in source table for this month, check existing manual entries
                if ($adj_dec == 0 && $adj_inc == 0 && isset($existing_ledger[$acc_code][$month_no])) {
                    $row = $existing_ledger[$acc_code][$month_no];
                    $adj_dec = $row->debt_adj_dec;
                    $adj_inc = $row->debt_adj_inc;
                    $adj_note = $row->adj_note;
                }

                $balance_total = $current_balance_old + $debt_new + $adj_inc - $debt_receive - $adj_dec;

                // Aging Calculation (As of end of month)
                $aging_90 = 0; $aging_365 = 0; $aging_over = 0;
                $aging_rows = DB::table($tableName)
                    ->select(
                        DB::raw("SUM(CASE WHEN DATEDIFF('$month_end', $dateField) <= 90 THEN (debtor + adj_inc - receive - adj_dec) ELSE 0 END) as a90"),
                        DB::raw("SUM(CASE WHEN DATEDIFF('$month_end', $dateField) BETWEEN 91 AND 365 THEN (debtor + adj_inc - receive - adj_dec) ELSE 0 END) as a365"),
                        DB::raw("SUM(CASE WHEN DATEDIFF('$month_end', $dateField) > 365 THEN (debtor + adj_inc - receive - adj_dec) ELSE 0 END) as aover")
                    )
                    ->whereRaw("$dateField <= ?", [$month_end])
                    ->whereRaw("(debtor + adj_inc - receive - adj_dec) > 0")
                    ->first();
                
                if ($aging_rows) {
                    $aging_90 = $aging_rows->a90 ?? 0;
                    $aging_365 = $aging_rows->a365 ?? 0;
                    $aging_over = $aging_rows->aover ?? 0;
                }

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
                        'aging_90' => $aging_90,
                        'aging_365' => $aging_365,
                        'aging_over' => $aging_over,
                        'updated_at' => now(),
                    ]
                );
                $processed_count++;
            }
        }
        return response()->json(['status' => 'success', 'count' => $processed_count]);
    }
}
