<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\Rep_ucs;
use App\Models\Rep_ucsexcel;
use App\Models\Rep_ofc;
use App\Models\Rep_ofcexcel;
use App\Models\Rep_sss;
use App\Models\Rep_sssexcel;
use App\Models\Rep_lgo;
use App\Models\Rep_lgoexcel;
use App\Models\Rep_bkk;
use App\Models\Rep_bkkexcel;
use App\Models\Rep_bmt;
use App\Models\Rep_bmtexcel;
use App\Models\Rep_srt;
use App\Models\Rep_srtexcel;
use App\Models\Rep_pvt;
use App\Models\Rep_pvtexcel;

class ImportRepController extends Controller
{
    // Check Login Permissions
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'นำเข้าข้อมูล'], 403);
                }
                return $next($request);
            }
        ]);
    }

    /**
     * ตรวจหาการจับคู่คอลัมน์ (Column Mapping) โดยอัตโนมัติจากหัวตาราง Excel ใน Row 5-8
     */
    protected function detectRepColMapping($sheet, array $defaultMapping = [])
    {
        $mapping = $defaultMapping;
        $headerRow = 0;
        
        for ($r = 5; $r <= 8; $r++) {
            $cellVal = (string)$sheet->getCell('D' . $r)->getValue();
            if (stripos($cellVal, 'HN') !== false) {
                $headerRow = $r;
                break;
            }
        }
        
        if ($headerRow > 0) {
            $detected = [];
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn($headerRow));
            for ($c = 1; $c <= min($highestCol, 120); $c++) {
                $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $h1 = trim((string)$sheet->getCell($colStr . $headerRow)->getValue());
                $h2 = trim((string)$sheet->getCell($colStr . ($headerRow + 1))->getValue());
                $headerText = preg_replace('/\s+/', ' ', mb_strtoupper($h1 . ' ' . $h2, 'UTF-8'));
                
                if ($headerText === '') continue;
                
                if (str_contains($headerText, 'REP') && !str_contains($headerText, 'TYPE')) {
                    $detected[$c] = 'repno';
                } elseif (str_contains($headerText, 'ลำดับ')) {
                    $detected[$c] = 'no';
                } elseif (str_contains($headerText, 'TRAN_ID') || str_contains($headerText, 'TRAN ID')) {
                    $detected[$c] = 'tran_id';
                } elseif ($h1 === 'HN') {
                    $detected[$c] = 'hn';
                } elseif ($h1 === 'AN') {
                    $detected[$c] = 'an';
                } elseif ($h1 === 'PID' || $h1 === 'CID') {
                    $detected[$c] = 'cid';
                } elseif (str_contains($headerText, 'ชื่อ') && str_contains($headerText, 'สกุล')) {
                    $detected[$c] = 'pt_name';
                } elseif (str_contains($headerText, 'ประเภทผู้ป่วย')) {
                    $detected[$c] = 'pt_type';
                } elseif (str_contains($headerText, 'ชดเชยสุทธิ') || str_contains($headerText, 'รวมเงินค่าบริการทั้งหมด')) {
                    $detected[$c] = 'net_compensate_nhso';
                } elseif (str_contains($headerText, 'ต้นสังกัด') || str_contains($headerText, 'PP (รับจาก สปสช.)') || str_contains($headerText, 'PP (รับจาก')) {
                    $detected[$c] = 'net_compensate_employer';
                } elseif (str_contains($headerText, 'ชดเชยจาก')) {
                    $detected[$c] = 'compensate_from';
                } elseif (str_contains($headerText, 'ERROR CODE') || str_contains($headerText, 'ERROR') || str_contains($headerText, 'รหัส C')) {
                    $detected[$c] = 'error_code';
                } elseif (str_contains($headerText, 'กองทุนหลัก') || str_contains($headerText, 'กองทุน')) {
                    $detected[$c] = 'main_fund';
                } elseif (str_contains($headerText, 'กองทุนย่อย')) {
                    $detected[$c] = 'sub_fund';
                } elseif (str_contains($headerText, 'ประเภทบริการ')) {
                    $detected[$c] = 'service_type';
                } elseif (str_contains($headerText, 'การรับส่งต่อ')) {
                    $detected[$c] = 'refer_type';
                } elseif (str_contains($headerText, 'การมีสิทธิ')) {
                    $detected[$c] = 'has_right';
                } elseif (str_contains($headerText, 'การใช้สิทธิ')) {
                    $detected[$c] = 'use_right';
                } elseif (str_contains($headerText, 'สิทธิหลัก')) {
                    $detected[$c] = 'maininscl';
                } elseif (str_contains($headerText, 'สิทธิรอง') || str_contains($headerText, 'สิทธิย่อย')) {
                    $detected[$c] = 'subinscl';
                } elseif ($h1 === 'HREF') {
                    $detected[$c] = 'href';
                } elseif ($h1 === 'HCODE') {
                    $detected[$c] = 'hcode';
                } elseif ($h1 === 'PROV1') {
                    $detected[$c] = 'prov1';
                } elseif (str_contains($headerText, 'รหัสหน่วยงาน') || $h1 === 'HMAIN') {
                    $detected[$c] = 'hmain';
                } elseif (str_contains($headerText, 'ชื่อหน่วยงาน') || $h1 === 'PROV2') {
                    $detected[$c] = 'prov2';
                } elseif ($h1 === 'PROJ') {
                    $detected[$c] = 'proj';
                } elseif ($h1 === 'PA') {
                    $detected[$c] = 'pa';
                } elseif ($h1 === 'DRG') {
                    $detected[$c] = 'drg';
                } elseif ($h1 === 'RW') {
                    $detected[$c] = 'rw';
                } elseif (str_contains($headerText, 'เรียกเก็บ') && !str_contains($headerText, 'CENTRAL') && !str_contains($headerText, 'PP')) {
                    $detected[$c] = 'charge_total';
                } elseif (str_contains($headerText, 'เบิกได้')) {
                    $detected[$c] = 'charge_vehicle_drug_device';
                } elseif (str_contains($headerText, 'เบิกไม่ได้')) {
                    $detected[$c] = 'charge_central_reimburse';
                } elseif (str_contains($headerText, 'ชำระเอง')) {
                    $detected[$c] = 'self_pay';
                } elseif (str_contains($headerText, 'อัตราจ่าย')) {
                    $detected[$c] = 'payrate_point';
                } elseif (str_contains($headerText, 'ล่าช้า') && str_contains($headerText, 'เปอร์เซ็นต์')) {
                    $detected[$c] = 'delay_percent';
                } elseif (str_contains($headerText, 'ล่าช้า')) {
                    $detected[$c] = 'delay_ps';
                } elseif ($h1 === 'CCUF') {
                    $detected[$c] = 'ccuf';
                } elseif ($h1 === 'ADJRW' || str_contains($headerText, 'ADJRW_NHSO')) {
                    $detected[$c] = 'adjrw_nhso';
                } elseif (str_contains($headerText, 'พรบ')) {
                    $detected[$c] = 'act_amount';
                } elseif ($h1 === 'ORS') {
                    $detected[$c] = 'pay_pattern';
                } elseif ($h1 === 'VA') {
                    $detected[$c] = 'va';
                } elseif (str_contains($headerText, 'AUDIT RESULTS')) {
                    $detected[$c] = 'audit_results';
                } elseif (str_contains($headerText, 'SEQ NO')) {
                    $detected[$c] = 'seq_no';
                } elseif (str_contains($headerText, 'INVOICE NO')) {
                    $detected[$c] = 'invoice_no';
                } elseif (str_contains($headerText, 'INVOICE LT')) {
                    $detected[$c] = 'invoice_lt';
                }
            }
            if (count($detected) >= 15) {
                $mapping = $detected;
            }
        }
        return $mapping;
    }

    // rep_index -----------------------------------------------------------------------------------------------------
    public function rep_index(Request $request)
    {
        return view('import.rep_index');
    }

    // rep_ucs -----------------------------------------------------------------------------------------------------
    public function rep_ucs(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_ucs = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_ucs r2 
                    WHERE r2.hn = rep_ucs.hn 
                      AND r2.id != rep_ucs.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_ucs.rep_type = 'IP' AND r2.an = rep_ucs.an AND r2.rep_type = 'IP')
                          OR
                          (rep_ucs.rep_type = 'OP' AND r2.vstdate = rep_ucs.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_ucs
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_ucs',
            compact('rep_ucs', 'budget_year_select', 'budget_year')
        );
    }

    // rep_ucs_getChartData ---------------------------------------------------------------------------------------
    public function rep_ucs_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_ucs')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

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
        $opTotals = [];
        $ipTotals = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            if (isset($rawData[$m])) {
                $opTotals[] = floatval($rawData[$m]->op_receive);
                $ipTotals[] = floatval($rawData[$m]->ip_receive);
            } else {
                $opTotals[] = 0.00;
                $ipTotals[] = 0.00;
            }
        }

        return response()->json([
            'labels' => $labels,
            'op_totals' => $opTotals,
            'ip_totals' => $ipTotals
        ]);
    }

    // rep_ucs_getFailDetails -------------------------------------------------------------------------------------
    public function rep_ucs_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        // Fetch failed patient list with subqueries to check status
        $patients = DB::table('rep_ucs as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->whereRaw("r.error_code NOT REGEXP '^(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT)(,(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT))*$'")
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_ucs r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        // Format dates, numbers, and resolution status badges
        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            // Determine status
            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        // Fetch error code summary for chart
        $errorSummary = DB::table('rep_ucs')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '^(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT)(,(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT))*$'")
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    // rep_ucs_getCCodeChartData ----------------------------------------------------------------------------------
    public function rep_ucs_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_ucs')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_ucs r2 
                WHERE r2.hn = rep_ucs.hn 
                  AND r2.id != rep_ucs.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_ucs.rep_type = 'IP' AND r2.an = rep_ucs.an AND r2.rep_type = 'IP')
                      OR
                      (rep_ucs.rep_type = 'OP' AND r2.vstdate = rep_ucs.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $rawData = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(15) // limit to top 15 errors to keep chart readable
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rawData as $row) {
            $labels[] = $row->error_code;
            $counts[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'counts' => $counts
        ]);
    }

    // rep_ucs_save -------------------------------------------------------------------------------------------------
    public function rep_ucs_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        /* ======================================================
        1) Clear staging
        ====================================================== */
        Rep_ucsexcel::truncate();

        // Numeric fields list for sanitization
        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-DP)
        $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 and 10 (dates) handled manually
            11 => 'net_compensate_nhso',
            12 => 'net_compensate_employer',
            13 => 'compensate_from',
            14 => 'error_code',
            15 => 'main_fund',
            16 => 'sub_fund',
            17 => 'service_type',
            18 => 'refer_type',
            19 => 'has_right',
            20 => 'use_right',
            21 => 'chk',
            22 => 'maininscl',
            23 => 'subinscl',
            24 => 'href',
            25 => 'hcode',
            26 => 'hmain',
            27 => 'prov1',
            28 => 'rg1',
            29 => 'hmain2',
            30 => 'prov2',
            31 => 'rg2',
            32 => 'dmis_hmain3',
            33 => 'da',
            34 => 'proj',
            35 => 'pa',
            36 => 'drg',
            37 => 'rw',
            38 => 'ca_type',
            39 => 'charge_non_vehicle_drug_device',
            40 => 'charge_vehicle_drug_device',
            41 => 'charge_total',
            42 => 'charge_central_reimburse',
            43 => 'self_pay',
            44 => 'payrate_point',
            45 => 'delay_ps',
            46 => 'delay_percent',
            47 => 'ccuf',
            48 => 'adjrw_nhso',
            49 => 'adjrw2',
            50 => 'compensate_amount',
            51 => 'act_amount',
            52 => 'salary_percent',
            53 => 'salary_amount',
            54 => 'compensate_after_salary',
            55 => 'hc_iphc',
            56 => 'hc_ophc',
            57 => 'ae_opae',
            58 => 'ae_ipnb',
            59 => 'ae_ipuc',
            60 => 'ae_ip3sss',
            61 => 'ae_ip7sss',
            62 => 'ae_carae',
            63 => 'ae_caref',
            64 => 'ae_caref_puc',
            65 => 'inst_opinst',
            66 => 'inst_ipinst',
            67 => 'ip_ipaec',
            68 => 'ip_ipaer',
            69 => 'ip_ipinrgc',
            70 => 'ip_ipinrgr',
            71 => 'ip_ipinspsn',
            72 => 'ip_ipprcc',
            73 => 'ip_ipprcc_puc',
            74 => 'ip_ipbkk_inst',
            75 => 'ip_ip_ontop',
            76 => 'dmis_cataract',
            77 => 'dmis_ssj_workload',
            78 => 'dmis_hosp_workload',
            79 => 'dmis_catinst',
            80 => 'dmis_rc',
            81 => 'dmis_rc_workload',
            82 => 'dmis_rcuhosc',
            83 => 'dmis_rcuhosc_workload',
            84 => 'dmis_rcuhosr',
            85 => 'dmis_rcuhosr_workload',
            86 => 'dmis_llop',
            87 => 'dmis_llrgc',
            88 => 'dmis_llrgr',
            89 => 'dmis_lp',
            90 => 'dmis_stroke_stemi_drug',
            91 => 'dmis_dmidml',
            92 => 'dmis_pp',
            93 => 'dmis_dmishd',
            94 => 'dmis_dmicnt',
            95 => 'dmis_palliative_care',
            96 => 'dmis_dm',
            97 => 'drug',
            98 => 'opbkk_hc',
            99 => 'opbkk_dent',
            100 => 'opbkk_drug',
            101 => 'opbkk_fs',
            102 => 'opbkk_others',
            103 => 'opbkk_hsub',
            104 => 'opbkk_nhso',
            105 => 'deny_hc',
            106 => 'deny_ae',
            107 => 'deny_inst',
            108 => 'deny_ip',
            109 => 'deny_dmis',
            110 => 'base_rate_old',
            111 => 'base_rate_add',
            112 => 'base_rate_net',
            113 => 'fs',
            114 => 'va',
            115 => 'remark',
            116 => 'audit_results',
            117 => 'pay_pattern',
            118 => 'seq_no',
            119 => 'invoice_no',
            120 => 'invoice_lt'
        ];

        /* ======================================================
        2) Read Excel → insert staging
        ====================================================== */
        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0); // Renders from the first sheet (Detail)
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 9; // Data starts at Row 9

            $buffer = [];

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);

            for ($row = $startRow; $row <= $row_limit; $row++) {
                // Check if HN is empty
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for discharge date: " . $rawDch);
                    }
                }

                $rowData = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                // Parse columns using active mapping
                for ($c = 1; $c <= 120; $c++) {
                    if ($c === 9 || $c === 10 || !isset($activeColMapping[$c])) {
                        continue; // Date fields handled above
                    }

                    $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val = $sheet->getCell($colChar . $row)->getValue();
                    $fieldName = $activeColMapping[$c];

                    if (in_array($fieldName, $numericFields)) {
                        if ($val === '-' || $val === '' || $val === null) {
                            $rowData[$fieldName] = null;
                        } else {
                            // Clean commas
                            $rowData[$fieldName] = (double) str_replace(',', '', $val);
                        }
                    } else {
                        if ($val === '-' || $val === '' || $val === null) {
                            $rowData[$fieldName] = null;
                        } else {
                            $trimmedVal = trim((string)$val);
                            
                            // กรองค่า Error Code ป้องกันชื่อกองทุนหลุดเข้าไป
                            if ($fieldName === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $rowData['error_code'] = null;
                                } else {
                                    $rowData['error_code'] = $trimmedVal;
                                }
                            } else {
                                $rowData[$fieldName] = $trimmedVal;
                            }
                        }
                    }
                }

                $buffer[] = $rowData;

                if (count($buffer) === 1000) {
                    Rep_ucsexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_ucsexcel::insert($buffer);
            }

            unset($spreadsheet);
            gc_collect_cycles();
        }

        /* ======================================================
        3) Merge staging → rep_ucs
        ====================================================== */
        DB::transaction(function () {
            Rep_ucsexcel::chunk(1000, function ($rows) {
                foreach ($rows as $value) {
                    $valueArr = $value->toArray();
                    unset($valueArr['id']); // Remove auto-increment ID

                    Rep_ucs::updateOrInsert(
                        [
                            'repno' => $value->repno,
                            'no' => $value->no,
                        ],
                        $valueArr
                    );
                }
            });
        });

        /* ======================================================
        4) Clear staging
        ====================================================== */
        Rep_ucsexcel::truncate();

        // Automatically determine the budget year from the first file name to redirect to the correct page
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');
        if (!$budget_year_now) {
            $budget_year_now = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $redirectYear = $budget_year_now;
        if (!empty($allFileNames)) {
            $firstFile = $allFileNames[0];
            preg_match('/25\d{2}/', $firstFile, $yrMatches);
            if (!empty($yrMatches)) {
                $fileYear = (int)$yrMatches[0];
                $pos = strpos($firstFile, $yrMatches[0]);
                if ($pos !== false) {
                    $monthVal = (int)substr($firstFile, $pos + 4, 2);
                    if ($monthVal >= 10) {
                        $fileYear += 1;
                    }
                }
                $redirectYear = $fileYear;
            }
        }

        return redirect()
            ->route('rep_ucs', ['budget_year' => $redirectYear])
            ->with('rep_success', implode(', ', $allFileNames));
    }


    // rep_ucs_detail -----------------------------------------------------------------------------------------------
    public function rep_ucs_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type; // 'opd' or 'ipd'

            $query = DB::table('rep_ucs')
                ->select([
                    'rep_type AS dep',
                    'rep_filename',
                    'repno',
                    'hn',
                    'an',
                    'pt_name',
                    'datetimeadm',
                    'datetimedch',
                    'proj',
                    'drg',
                    'rw',
                    'charge_total',
                    'net_compensate_nhso',
                    'net_compensate_employer',
                    'compensate_from',
                    'error_code',
                    'deny_hc',
                    'deny_ae',
                    'deny_inst',
                    'deny_ip',
                    'deny_dmis',
                    'remark',
                    'audit_results',
                    'pay_pattern',
                    'invoice_no'
                ]);

            if ($type == 'opd') {
                $query->whereRaw('DATE(datetimeadm) BETWEEN ? AND ?', [$start_date, $end_date])
                    ->where('rep_type', 'OP');
            } else { // ipd
                $query->whereRaw('DATE(datetimedch) BETWEEN ? AND ?', [$start_date, $end_date])
                    ->where('rep_type', 'IP');
            }

            // Search filter
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('repno', 'like', "%$search%")
                        ->orWhere('hn', 'like', "%$search%")
                        ->orWhere('an', 'like', "%$search%")
                        ->orWhere('pt_name', 'like', "%$search%")
                        ->orWhere('rep_filename', 'like', "%$search%");
                });
            }

            // Grouping and Ordering
            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            // Export to Excel
            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_UCS_Detail_' . strtoupper($type));

                // Headers
                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                // Auto-fit columns
                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_UCS_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            // AJAX DataTables Server-Side Processing
            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            // Columns definition for ordering
            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_ucs_detail', compact('start_date', 'end_date'));
    }

    // rep_ucs_detail_opd -------------------------------------------------------------------------------------------
    public function rep_ucs_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_ucs_detail_opd', compact('start_date', 'end_date'));
    }

    // rep_ucs_detail_ipd -------------------------------------------------------------------------------------------
    public function rep_ucs_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_ucs_detail_ipd', compact('start_date', 'end_date'));
    }

    // =============================================================================================================
    // REP OFC METHODS (Civil Servant)
    // =============================================================================================================

    public function rep_ofc(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

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

        $rep_ofc = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_ofc r2 
                    WHERE r2.hn = rep_ofc.hn 
                      AND r2.id != rep_ofc.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_ofc.rep_type = 'IP' AND r2.an = rep_ofc.an AND r2.rep_type = 'IP')
                          OR
                          (rep_ofc.rep_type = 'OP' AND r2.vstdate = rep_ofc.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_ofc
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_ofc',
            compact('rep_ofc', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_ofc_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_ofc')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get();

        $monthlyData = array_fill(1, 12, ['op' => 0, 'ip' => 0]);
        foreach ($rawData as $row) {
            $m = (int) $row->month_no;
            if ($m >= 1 && $m <= 12) {
                $monthlyData[$m] = [
                    'op' => (float) $row->op_receive,
                    'ip' => (float) $row->ip_receive
                ];
            }
        }

        $fiscalMonths = [10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.', 1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.'];
        $labels = [];
        $opTotals = [];
        $ipTotals = [];
        foreach ($fiscalMonths as $mNum => $mTh) {
            $labels[] = $mTh;
            $opTotals[] = $monthlyData[$mNum]['op'];
            $ipTotals[] = $monthlyData[$mNum]['ip'];
        }

        return response()->json([
            'labels' => $labels,
            'op_totals' => $opTotals,
            'ip_totals' => $ipTotals
        ]);
    }

    public function rep_ofc_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_ofc as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->whereRaw("r.error_code NOT REGEXP '^(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT)(,(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT))*$'")
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_ofc r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_ofc')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '^(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT)(,(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT))*$'")
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_ofc_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_ofc')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_ofc r2 
                WHERE r2.hn = rep_ofc.hn 
                  AND r2.id != rep_ofc.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_ofc.rep_type = 'IP' AND r2.an = rep_ofc.an AND r2.rep_type = 'IP')
                      OR
                      (rep_ofc.rep_type = 'OP' AND r2.vstdate = rep_ofc.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $rawData = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(15) // limit to top 15 errors to keep chart readable
            ->get();

        $labels = [];
        $counts = [];
        foreach ($rawData as $row) {
            $labels[] = $row->error_code;
            $counts[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'counts' => $counts
        ]);
    }

    public function rep_ofc_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_ofcexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for OFC
        $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 and 10 (dates) handled manually
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ (Col K)
            12 => 'net_compensate_employer', // PP (Col L)
            13 => 'main_fund', // กองทุน (Col M)
            14 => 'error_code', // Error Code (Col N)
            15 => 'service_type', // ประเภทบริการ (Col O)
            16 => 'refer_type', // การรับส่งต่อ (Col P)
            17 => 'has_right', // การมีสิทธิ (Col Q)
            18 => 'use_right', // การใช้สิทธิ (Col R)
            19 => 'maininscl', // สิทธิหลัก (Col S)
            20 => 'subinscl', // สิทธิรอง (Col T)
            21 => 'href', // HREF (Col U)
            22 => 'hcode', // HCODE (Col V)
            23 => 'prov1', // PROV1 (Col W)
            24 => 'hmain', // รหัสหน่วยงาน (Col X)
            25 => 'prov2', // ชื่อหน่วยงาน (Col Y)
            26 => 'proj', // PROJ (Col Z)
            27 => 'pa', // PA (Col AA)
            28 => 'drg', // DRG (Col AB)
            29 => 'rw', // RW (Col AC)
            30 => 'charge_total', // เรียกเก็บ (Col AD)
            31 => 'charge_non_vehicle_drug_device', // PP charge or similar (Col AE)
            32 => 'charge_vehicle_drug_device', // เบิกได้ (Col AF)
            33 => 'charge_central_reimburse', // เบิกไม่ได้ (Col AG)
            34 => 'self_pay', // ชำระเอง (Col AH)
            35 => 'payrate_point', // อัตราจ่าย (Col AI)
            36 => 'delay_ps', // ล่าช้า (PS) (Col AJ)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์ (Col AK)
            38 => 'ccuf', // CCUF (Col AL)
            39 => 'adjrw_nhso', // AdjRW (Col AM)
            40 => 'act_amount', // พรบ. (Col AN)
            41 => 'hc_iphc', // IPCS (Col AO)
            42 => 'ae_opae', // OPCS (Col AP)
            43 => 'ae_ipnb', // PACS (Col AQ)
            44 => 'inst_opinst', // INSTCS (Col AR)
            45 => 'compensate_amount', // OTCS (Col AS)
            46 => 'salary_amount', // PP (Col AT)
            47 => 'drug', // DRUG (Col AU)
            48 => 'deny_ip', // Deny IPCS (Col AV)
            49 => 'deny_hc', // Deny OPCS (Col AW)
            50 => 'deny_ae', // Deny PACS (Col AX)
            51 => 'deny_inst', // Deny INSTCS (Col AY)
            52 => 'deny_dmis', // Deny OTCS (Col AZ)
            53 => 'pay_pattern', // ORS (Col BA)
            54 => 'va', // VA (Col BB)
            55 => 'audit_results', // AUDIT RESULTS (Col BC)
            56 => 'seq_no', // SEQ NO (Col BD)
            57 => 'invoice_no', // INVOICE NO (Col BE)
            58 => 'invoice_lt' // INVOICE LT (Col BF)
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPCS... or IPCS)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPCS_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // OFC e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for discharge date: " . $rawDch);
                    }
                }

                $rowData = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                for ($c = 1; $c <= 120; $c++) {
                    if ($c === 9 || $c === 10 || !isset($activeColMapping[$c])) {
                        continue; // Date fields and unmapped columns handled manually/skipped
                    }

                    $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val = $sheet->getCell($colChar . $row)->getValue();
                    $fieldName = $activeColMapping[$c];

                    if (in_array($fieldName, $numericFields)) {
                        if ($val === '-' || $val === '' || $val === null) {
                            $rowData[$fieldName] = null;
                        } else {
                            $rowData[$fieldName] = (double) str_replace(',', '', $val);
                        }
                    } else {
                        if ($val === '-' || $val === '' || $val === null) {
                            $rowData[$fieldName] = null;
                        } else {
                            $trimmedVal = trim((string)$val);
                            
                            // กรองค่า Error Code ป้องกันชื่อกองทุน เช่น OPCS, OTCS, INSTCS หลุดเข้าไป
                            if ($fieldName === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $rowData['error_code'] = null;
                                } else {
                                    $rowData['error_code'] = $trimmedVal;
                                }
                            } else {
                                $rowData[$fieldName] = $trimmedVal;
                            }
                        }
                    }
                }

                $buffer[] = $rowData;

                if (count($buffer) === 1000) {
                    Rep_ofcexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_ofcexcel::insert($buffer);
            }

            unset($spreadsheet);
            gc_collect_cycles();
        }

        DB::transaction(function () {
            Rep_ofcexcel::chunk(1000, function ($rows) {
                foreach ($rows as $value) {
                    $valueArr = $value->toArray();
                    unset($valueArr['id']); // Remove auto-increment ID

                    Rep_ofc::updateOrInsert(
                        [
                            'repno' => $value->repno,
                            'no' => $value->no,
                        ],
                        $valueArr
                    );
                }
            });
        });

        Rep_ofcexcel::truncate();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');
        if (!$budget_year_now) {
            $budget_year_now = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        // Automatically determine the budget year from the first file name to redirect to the correct page
        $redirectYear = $budget_year_now;
        if (!empty($allFileNames)) {
            $firstFile = $allFileNames[0];
            preg_match('/25\d{2}/', $firstFile, $yrMatches);
            if (!empty($yrMatches)) {
                $fileYear = (int)$yrMatches[0];
                // extract month following the year (e.g. 25680922 -> 2568, month 09)
                $pos = strpos($firstFile, $yrMatches[0]);
                if ($pos !== false) {
                    $monthVal = (int)substr($firstFile, $pos + 4, 2);
                    if ($monthVal >= 10) {
                        $fileYear += 1;
                    }
                }
                $redirectYear = $fileYear;
            }
        }

        return redirect()
            ->route('rep_ofc', ['budget_year' => $redirectYear])
            ->with('rep_success', implode(', ', $allFileNames));
    }

    public function rep_ofc_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type; // 'opd' or 'ipd'

            $query = DB::table('rep_ofc')
                ->select([
                    'rep_type AS dep',
                    'rep_filename',
                    'repno',
                    'hn',
                    'an',
                    'pt_name',
                    'datetimeadm',
                    'datetimedch',
                    'proj',
                    'drg',
                    'rw',
                    'charge_total',
                    'net_compensate_nhso',
                    'net_compensate_employer',
                    'compensate_from',
                    'error_code',
                    'deny_hc',
                    'deny_ae',
                    'deny_inst',
                    'deny_ip',
                    'deny_dmis',
                    'remark',
                    'audit_results',
                    'pay_pattern',
                    'invoice_no'
                ]);

            if ($type == 'opd') {
                $query->whereRaw('DATE(datetimeadm) BETWEEN ? AND ?', [$start_date, $end_date])
                    ->where('rep_type', 'OP');
            } else { // ipd
                $query->whereRaw('DATE(datetimedch) BETWEEN ? AND ?', [$start_date, $end_date])
                    ->where('rep_type', 'IP');
            }

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('repno', 'like', "%$search%")
                        ->orWhere('hn', 'like', "%$search%")
                        ->orWhere('an', 'like', "%$search%")
                        ->orWhere('pt_name', 'like', "%$search%")
                        ->orWhere('rep_filename', 'like', "%$search%");
                });
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_OFC_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_OFC_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_ofc_detail', compact('start_date', 'end_date'));
    }

    public function rep_ofc_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_ofc_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_ofc_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_ofc_detail_ipd', compact('start_date', 'end_date'));
    }

    // rep_sss -----------------------------------------------------------------------------------------------------
    public function rep_sss(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_sss = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_sss r2 
                    WHERE r2.hn = rep_sss.hn 
                      AND r2.id != rep_sss.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_sss.rep_type = 'IP' AND r2.an = rep_sss.an AND r2.rep_type = 'IP')
                          OR
                          (rep_sss.rep_type = 'OP' AND r2.vstdate = rep_sss.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_sss
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_sss',
            compact('rep_sss', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_sss_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_sss')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_sss_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_sss as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_sss r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_sss')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_sss_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_sss')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_sss r2 
                WHERE r2.hn = rep_sss.hn 
                  AND r2.id != rep_sss.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_sss.rep_type = 'IP' AND r2.an = rep_sss.an AND r2.rep_type = 'IP')
                      OR
                      (rep_sss.rep_type = 'OP' AND r2.vstdate = rep_sss.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_sss_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_sssexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BV) for SSS
        $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'main_fund', // กรณีที่เบิก
            12 => 'service_type', // ประเภทบริการ
            13 => 'refer_type', // การรับส่งต่อ
            14 => 'has_right', // การมีสิทธิ
            15 => 'use_right', // การใช้สิทธิ
            16 => 'chk', // CHK
            17 => 'maininscl', // สิทธิหลัก
            18 => 'subinscl', // สิทธิย่อย
            19 => 'href', // HREF
            20 => 'hcode', // HCODE
            21 => 'hmain', // HMAIN
            22 => 'prov1', // PROV1
            23 => 'hmain2', // HMAIN2
            24 => 'prov2', // PROV2
            25 => 'proj', // PROJ
            30 => 'drg', // DRG
            31 => 'rw', // RW
            32 => 'adjrw_nhso', // AdjRW
            33 => 'self_pay', // ชำระเอง
            34 => 'payrate_point', // อัตราจ่ายที่กำหนด
            35 => 'delay_ps', // ระยะเวลาส่งข้อมูล (PS)
            61 => 'net_compensate_nhso', // รวมเงินค่าบริการทั้งหมด
            62 => 'error_code', // Error Code
            63 => 'deny_ip', // Deny IP
            64 => 'deny_hc', // OP
            65 => 'deny_ae', // AE
            66 => 'deny_inst', // HC
            67 => 'deny_dmis', // INT
            69 => 'va', // VA
            70 => 'remark', // REMARK
            71 => 'audit_results', // AUDIT RESULTS
            72 => 'seq_no', // SEQ NO
            73 => 'invoice_no', // INVOICE NO
            74 => 'invoice_lt' // INVOICE LT
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPSSS... or IPSSS)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPSSS_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 9; // SSS e-Claim Excel files data starts at Row 9

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        // Retry with d/m/Y format
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_sssexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_sssexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_sss')->where('rep_filename', $fName)->delete();
            }

            Rep_sssexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_sss::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_sssexcel::truncate();

        // Redirect back to the budget year of the last imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_sss', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP SSS สำเร็จ!');
    }

    public function rep_sss_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_sss as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_SSS_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_SSS_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_sss_detail', compact('start_date', 'end_date'));
    }

    public function rep_sss_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_sss_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_sss_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_sss_detail_ipd', compact('start_date', 'end_date'));
    }

    // rep_lgo -----------------------------------------------------------------------------------------------------
    public function rep_lgo(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_lgo = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_lgo r2 
                    WHERE r2.hn = rep_lgo.hn 
                      AND r2.id != rep_lgo.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_lgo.rep_type = 'IP' AND r2.an = rep_lgo.an AND r2.rep_type = 'IP')
                          OR
                          (rep_lgo.rep_type = 'OP' AND r2.vstdate = rep_lgo.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_lgo
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_lgo',
            compact('rep_lgo', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_lgo_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_lgo')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_lgo_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_lgo as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_lgo r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_lgo')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_lgo_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_lgo')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_lgo r2 
                WHERE r2.hn = rep_lgo.hn 
                  AND r2.id != rep_lgo.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_lgo.rep_type = 'IP' AND r2.an = rep_lgo.an AND r2.rep_type = 'IP')
                      OR
                      (rep_lgo.rep_type = 'OP' AND r2.vstdate = rep_lgo.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_lgo_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_lgoexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for LGO
        $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ
            12 => 'net_compensate_employer', // PP
            13 => 'main_fund', // กองทุน
            14 => 'error_code', // Error Code
            15 => 'service_type', // ประเภทบริการ
            16 => 'refer_type', // การรับส่งต่อ
            17 => 'has_right', // การมีสิทธิ
            18 => 'use_right', // การใช้สิทธิ
            19 => 'maininscl', // สิทธิหลัก
            20 => 'subinscl', // สิทธิรอง
            21 => 'href', // HREF
            22 => 'hcode', // HCODE
            23 => 'prov1', // PROV1
            24 => 'hmain', // รหัสหน่วยงาน
            25 => 'prov2', // ชื่อหน่วยงาน
            26 => 'proj', // PROJ
            27 => 'pa', // PA
            28 => 'drg', // DRG
            29 => 'rw', // RW
            30 => 'charge_total', // เรียกเก็บ
            31 => 'charge_non_vehicle_drug_device', // PP charge
            32 => 'charge_vehicle_drug_device', // เบิกได้
            33 => 'charge_central_reimburse', // เบิกไม่ได้
            34 => 'self_pay', // ชำระเอง
            35 => 'payrate_point', // อัตราจ่าย
            36 => 'delay_ps', // ล่าช้า (PS)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์
            38 => 'ccuf', // CCUF
            39 => 'adjrw_nhso', // AdjRW
            40 => 'act_amount', // พรบ.
            48 => 'deny_ip', // Deny IPLG (Col AV)
            49 => 'deny_hc', // Deny OPLG (Col AW)
            50 => 'deny_ae', // Deny PALG (Col AX)
            51 => 'deny_inst', // Deny INSTLG (Col AY)
            52 => 'deny_dmis', // Deny OTLG (Col AZ)
            53 => 'pay_pattern', // ORS (Col BA)
            54 => 'va', // VA (Col BB)
            55 => 'audit_results', // AUDIT RESULTS (Col BC)
            56 => 'seq_no', // SEQ NO (Col BD)
            57 => 'invoice_no', // INVOICE NO (Col BE)
            58 => 'invoice_lt' // INVOICE LT (Col BF)
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPLGO... or IPLGO)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPLGO_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // LGO e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_lgoexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_lgoexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_lgo')->where('rep_filename', $fName)->delete();
            }

            Rep_lgoexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_lgo::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_lgoexcel::truncate();

        // Redirect back to the budget year of the imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_lgo', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP LGO สำเร็จ!');
    }

    public function rep_lgo_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_lgo as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_LGO_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_LGO_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_lgo_detail', compact('start_date', 'end_date'));
    }

    public function rep_lgo_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_lgo_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_lgo_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_lgo_detail_ipd', compact('start_date', 'end_date'));
    }



// rep_bkk -----------------------------------------------------------------------------------------------------
    public function rep_bkk(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_bkk = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_bkk r2 
                    WHERE r2.hn = rep_bkk.hn 
                      AND r2.id != rep_bkk.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_bkk.rep_type = 'IP' AND r2.an = rep_bkk.an AND r2.rep_type = 'IP')
                          OR
                          (rep_bkk.rep_type = 'OP' AND r2.vstdate = rep_bkk.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_bkk
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_bkk',
            compact('rep_bkk', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_bkk_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_bkk')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_bkk_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_bkk as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_bkk r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_bkk')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_bkk_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_bkk')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_bkk r2 
                WHERE r2.hn = rep_bkk.hn 
                  AND r2.id != rep_bkk.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_bkk.rep_type = 'IP' AND r2.an = rep_bkk.an AND r2.rep_type = 'IP')
                      OR
                      (rep_bkk.rep_type = 'OP' AND r2.vstdate = rep_bkk.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_bkk_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_bkkexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for BKK
                $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ
            12 => 'net_compensate_employer', // PP
            13 => 'main_fund', // กองทุน
            14 => 'error_code', // Error Code
            15 => 'service_type', // ประเภทบริการ
            16 => 'refer_type', // การรับส่งต่อ
            17 => 'has_right', // การมีสิทธิ
            18 => 'use_right', // การใช้สิทธิ
            19 => 'maininscl', // สิทธิหลัก
            20 => 'subinscl', // สิทธิรอง
            21 => 'href', // HREF
            22 => 'hcode', // HCODE
            23 => 'prov1', // PROV1
            24 => 'hmain', // รหัสหน่วยงาน
            25 => 'prov2', // ชื่อหน่วยงาน
            26 => 'proj', // PROJ
            27 => 'pa', // PA
            28 => 'drg', // DRG
            29 => 'rw', // RW
            30 => 'charge_total', // เรียกเก็บ
            31 => 'charge_non_vehicle_drug_device', // PP charge
            32 => 'charge_vehicle_drug_device', // เบิกได้
            33 => 'charge_central_reimburse', // เบิกไม่ได้
            34 => 'self_pay', // ชำระเอง
            35 => 'payrate_point', // อัตราจ่าย
            36 => 'delay_ps', // ล่าช้า (PS)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์
            38 => 'ccuf', // CCUF
            39 => 'adjrw_nhso', // AdjRW
            40 => 'act_amount', // พรบ.
            49 => 'deny_ip', // Deny IP
            50 => 'deny_hc', // Deny OP
            51 => 'deny_ae', // Deny PALG
            52 => 'deny_inst', // Deny INST
            53 => 'deny_dmis', // Deny OT
            54 => 'pay_pattern', // ORS
            55 => 'va', // VA
            56 => 'audit_results', // AUDIT RESULTS
            57 => 'seq_no', // SEQ NO
            58 => 'invoice_no', // INVOICE NO
            59 => 'invoice_lt' // INVOICE LT
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPBKK... or IPBKK)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPBKK_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // BKK e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_bkkexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_bkkexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_bkk')->where('rep_filename', $fName)->delete();
            }

            Rep_bkkexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_bkk::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_bkkexcel::truncate();

        // Redirect back to the budget year of the imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_bkk', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP BKK สำเร็จ!');
    }

    public function rep_bkk_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_bkk as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_BKK_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_BKK_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_bkk_detail', compact('start_date', 'end_date'));
    }

    public function rep_bkk_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_bkk_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_bkk_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_bkk_detail_ipd', compact('start_date', 'end_date'));
    }

// rep_bmt -----------------------------------------------------------------------------------------------------
    public function rep_bmt(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_bmt = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_bmt r2 
                    WHERE r2.hn = rep_bmt.hn 
                      AND r2.id != rep_bmt.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_bmt.rep_type = 'IP' AND r2.an = rep_bmt.an AND r2.rep_type = 'IP')
                          OR
                          (rep_bmt.rep_type = 'OP' AND r2.vstdate = rep_bmt.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_bmt
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_bmt',
            compact('rep_bmt', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_bmt_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_bmt')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_bmt_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_bmt as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_bmt r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_bmt')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_bmt_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_bmt')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_bmt r2 
                WHERE r2.hn = rep_bmt.hn 
                  AND r2.id != rep_bmt.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_bmt.rep_type = 'IP' AND r2.an = rep_bmt.an AND r2.rep_type = 'IP')
                      OR
                      (rep_bmt.rep_type = 'OP' AND r2.vstdate = rep_bmt.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_bmt_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_bmtexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for BMT
                $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ
            12 => 'net_compensate_employer', // PP
            13 => 'main_fund', // กองทุน
            14 => 'error_code', // Error Code
            15 => 'service_type', // ประเภทบริการ
            16 => 'refer_type', // การรับส่งต่อ
            17 => 'has_right', // การมีสิทธิ
            18 => 'use_right', // การใช้สิทธิ
            19 => 'maininscl', // สิทธิหลัก
            20 => 'subinscl', // สิทธิรอง
            21 => 'href', // HREF
            22 => 'hcode', // HCODE
            23 => 'prov1', // PROV1
            24 => 'hmain', // รหัสหน่วยงาน
            25 => 'prov2', // ชื่อหน่วยงาน
            26 => 'proj', // PROJ
            27 => 'pa', // PA
            28 => 'drg', // DRG
            29 => 'rw', // RW
            30 => 'charge_total', // เรียกเก็บ
            31 => 'charge_non_vehicle_drug_device', // PP charge
            32 => 'charge_vehicle_drug_device', // เบิกได้
            33 => 'charge_central_reimburse', // เบิกไม่ได้
            34 => 'self_pay', // ชำระเอง
            35 => 'payrate_point', // อัตราจ่าย
            36 => 'delay_ps', // ล่าช้า (PS)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์
            38 => 'ccuf', // CCUF
            39 => 'adjrw_nhso', // AdjRW
            40 => 'act_amount', // พรบ.
            49 => 'deny_ip', // Deny IP
            50 => 'deny_hc', // Deny OP
            51 => 'deny_ae', // Deny PALG
            52 => 'deny_inst', // Deny INST
            53 => 'deny_dmis', // Deny OT
            54 => 'pay_pattern', // ORS
            55 => 'va', // VA
            56 => 'audit_results', // AUDIT RESULTS
            57 => 'seq_no', // SEQ NO
            58 => 'invoice_no', // INVOICE NO
            59 => 'invoice_lt' // INVOICE LT
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPBMT... or IPBMT)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPBMT_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // BMT e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_bmtexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_bmtexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_bmt')->where('rep_filename', $fName)->delete();
            }

            Rep_bmtexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_bmt::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_bmtexcel::truncate();

        // Redirect back to the budget year of the imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_bmt', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP BMT สำเร็จ!');
    }

    public function rep_bmt_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_bmt as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_BMT_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_BMT_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_bmt_detail', compact('start_date', 'end_date'));
    }

    public function rep_bmt_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_bmt_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_bmt_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_bmt_detail_ipd', compact('start_date', 'end_date'));
    }

// rep_srt -----------------------------------------------------------------------------------------------------
    public function rep_srt(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_srt = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_srt r2 
                    WHERE r2.hn = rep_srt.hn 
                      AND r2.id != rep_srt.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_srt.rep_type = 'IP' AND r2.an = rep_srt.an AND r2.rep_type = 'IP')
                          OR
                          (rep_srt.rep_type = 'OP' AND r2.vstdate = rep_srt.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_srt
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_srt',
            compact('rep_srt', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_srt_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_srt')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_srt_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_srt as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_srt r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_srt')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_srt_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_srt')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_srt r2 
                WHERE r2.hn = rep_srt.hn 
                  AND r2.id != rep_srt.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_srt.rep_type = 'IP' AND r2.an = rep_srt.an AND r2.rep_type = 'IP')
                      OR
                      (rep_srt.rep_type = 'OP' AND r2.vstdate = rep_srt.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_srt_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_srtexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for SRT
                $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ
            12 => 'net_compensate_employer', // PP
            13 => 'main_fund', // กองทุน
            14 => 'error_code', // Error Code
            15 => 'service_type', // ประเภทบริการ
            16 => 'refer_type', // การรับส่งต่อ
            17 => 'has_right', // การมีสิทธิ
            18 => 'use_right', // การใช้สิทธิ
            19 => 'maininscl', // สิทธิหลัก
            20 => 'subinscl', // สิทธิรอง
            21 => 'href', // HREF
            22 => 'hcode', // HCODE
            23 => 'prov1', // PROV1
            24 => 'hmain', // รหัสหน่วยงาน
            25 => 'prov2', // ชื่อหน่วยงาน
            26 => 'proj', // PROJ
            27 => 'pa', // PA
            28 => 'drg', // DRG
            29 => 'rw', // RW
            30 => 'charge_total', // เรียกเก็บ
            31 => 'charge_non_vehicle_drug_device', // PP charge
            32 => 'charge_vehicle_drug_device', // เบิกได้
            33 => 'charge_central_reimburse', // เบิกไม่ได้
            34 => 'self_pay', // ชำระเอง
            35 => 'payrate_point', // อัตราจ่าย
            36 => 'delay_ps', // ล่าช้า (PS)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์
            38 => 'ccuf', // CCUF
            39 => 'adjrw_nhso', // AdjRW
            40 => 'act_amount', // พรบ.
            49 => 'deny_ip', // Deny IP
            50 => 'deny_hc', // Deny OP
            51 => 'deny_ae', // Deny PALG
            52 => 'deny_inst', // Deny INST
            53 => 'deny_dmis', // Deny OT
            54 => 'pay_pattern', // ORS
            55 => 'va', // VA
            56 => 'audit_results', // AUDIT RESULTS
            57 => 'seq_no', // SEQ NO
            58 => 'invoice_no', // INVOICE NO
            59 => 'invoice_lt' // INVOICE LT
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPSRT... or IPSRT)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPSRT_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // SRT e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_srtexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_srtexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_srt')->where('rep_filename', $fName)->delete();
            }

            Rep_srtexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_srt::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_srtexcel::truncate();

        // Redirect back to the budget year of the imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_srt', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP SRT สำเร็จ!');
    }

    public function rep_srt_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_srt as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_SRT_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_SRT_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_srt_detail', compact('start_date', 'end_date'));
    }

    public function rep_srt_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_srt_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_srt_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_srt_detail_ipd', compact('start_date', 'end_date'));
    }

// rep_pvt -----------------------------------------------------------------------------------------------------
    public function rep_pvt(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 minutes

        /* ---------------- Budget Year Dropdown ---------------- */
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

        /* ---------------- Main summary query ---------------- */
        $rep_pvt = DB::select("
            SELECT
            rep_type AS dep,
            rep_filename,
            repno,
            MAX(is_appeal) AS is_appeal,
            COUNT(cid) AS count_cid,
            SUM(CASE WHEN error_code IS NULL OR error_code = '' OR error_code = '-' OR error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' THEN 1 ELSE 0 END) AS count_fail,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' AND error_code != '-' AND error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)' AND (
                EXISTS (
                    SELECT 1 
                    FROM rep_pvt r2 
                    WHERE r2.hn = rep_pvt.hn 
                      AND r2.id != rep_pvt.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                      AND (
                          (rep_pvt.rep_type = 'IP' AND r2.an = rep_pvt.an AND r2.rep_type = 'IP')
                          OR
                          (rep_pvt.rep_type = 'OP' AND r2.vstdate = rep_pvt.vstdate AND r2.rep_type = 'OP')
                      )
                )
            ) THEN 1 ELSE 0 END) AS count_resolved,
            SUM(charge_total) AS charge,
            SUM(net_compensate_nhso) AS receive_total
            FROM rep_pvt
            WHERE (CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename), 4) AS UNSIGNED)
                + (IF(CAST(SUBSTRING(rep_filename, LOCATE('25', rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0))) = ?
            GROUP BY rep_filename, rep_type, repno
            ORDER BY rep_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.rep_pvt',
            compact('rep_pvt', 'budget_year_select', 'budget_year')
        );
    }

    public function rep_pvt_getChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $rawData = DB::table('rep_pvt')
            ->select(
                DB::raw('CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) as month_no'),
                DB::raw("SUM(CASE WHEN rep_type = 'OP' THEN net_compensate_nhso ELSE 0 END) as op_receive"),
                DB::raw("SUM(CASE WHEN rep_type = 'IP' THEN net_compensate_nhso ELSE 0 END) as ip_receive")
            )
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->groupBy('month_no')
            ->get()
            ->keyBy('month_no');

        $monthOrder = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $byShort = substr($budget_year, -2);
        $prevByShort = substr($budget_year - 1, -2);

        $monthNames = [
            10 => 'ต.ค. ' . $prevByShort, 11 => 'พ.ย. ' . $prevByShort, 12 => 'ธ.ค. ' . $prevByShort,
            1 => 'ม.ค. ' . $byShort, 2 => 'ก.พ. ' . $byShort, 3 => 'มี.ค. ' . $byShort,
            4 => 'เม.ย. ' . $byShort, 5 => 'พ.ค. ' . $byShort, 6 => 'มิ.ย. ' . $byShort,
            7 => 'ก.ค. ' . $byShort, 8 => 'ส.ค. ' . $byShort, 9 => 'ก.ย. ' . $byShort
        ];

        $labels = [];
        $opData = [];
        $ipData = [];

        foreach ($monthOrder as $m) {
            $labels[] = $monthNames[$m];
            $opData[] = isset($rawData[$m]) ? (float)$rawData[$m]->op_receive : 0.0;
            $ipData[] = isset($rawData[$m]) ? (float)$rawData[$m]->ip_receive : 0.0;
        }

        return response()->json([
            'labels' => $labels,
            'opData' => $opData,
            'ipData' => $ipData
        ]);
    }

    public function rep_pvt_getFailDetails(Request $request)
    {
        $filename = $request->rep_filename;
        $type = $request->rep_type; // 'OP' or 'IP'
        $repno = $request->repno;

        $patients = DB::table('rep_pvt as r')
            ->where('r.rep_filename', $filename)
            ->where('r.rep_type', $type)
            ->where('r.repno', $repno)
            ->whereNotNull('r.error_code')
            ->where('r.error_code', '!=', '')
            ->where('r.error_code', '!=', '-')
            ->select([
                'r.id',
                'r.hn',
                'r.an',
                'r.pt_name',
                'r.vstdate',
                'r.dchdate',
                'r.error_code',
                'r.charge_total',
                'r.net_compensate_nhso',
                DB::raw("(
                    SELECT r2.repno 
                    FROM rep_pvt r2 
                    WHERE r2.hn = r.hn 
                      AND r2.id != r.id
                      AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno")
            ])
            ->get();

        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            if ($p->resolved_repno) {
                $status_text = 'ผ่านใน REP: ' . $p->resolved_repno;
                $status_color = 'success';
            } else {
                $status_text = 'ยังไม่ได้รับการแก้ไข';
                $status_color = 'danger';
            }

            $formattedPatients[] = [
                'hn' => $p->hn,
                'an' => $p->an ?: '-',
                'pt_name' => $p->pt_name,
                'service_date' => $service_date ? date('d/m/Y', strtotime($service_date)) : '-',
                'error_code' => $p->error_code,
                'charge_total' => number_format($p->charge_total, 2),
                'net_compensate_nhso' => number_format($p->net_compensate_nhso, 2),
                'status_text' => $status_text,
                'status_color' => $status_color
            ];
        }

        $errorSummary = DB::table('rep_pvt')
            ->where('rep_filename', $filename)
            ->where('rep_type', $type)
            ->where('repno', $repno)
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->select('error_code', DB::raw('count(*) as count'))
            ->groupBy('error_code')
            ->orderByDesc('count')
            ->get();

        $chartLabels = [];
        $chartCounts = [];
        foreach ($errorSummary as $row) {
            $chartLabels[] = $row->error_code;
            $chartCounts[] = (int) $row->count;
        }

        return response()->json([
            'patients' => $formattedPatients,
            'chart_labels' => $chartLabels,
            'chart_counts' => $chartCounts
        ]);
    }

    public function rep_pvt_getCCodeChartData(Request $request)
    {
        $budget_year = $request->budget_year ?: DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year) {
            $budget_year = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $type = $request->type ?: 'all'; // 'all', 'OP', 'IP'

        $query = DB::table('rep_pvt')
            ->select('error_code', DB::raw('count(*) as count'))
            ->whereRaw('(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename), 4) AS UNSIGNED) + IF(CAST(SUBSTRING(rep_filename, LOCATE("25", rep_filename) + 4, 2) AS UNSIGNED) >= 10, 1, 0)) = ?', [$budget_year])
            ->whereNotNull('error_code')
            ->where('error_code', '!=', '')
            ->where('error_code', '!=', '-')
            ->whereRaw("error_code NOT REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)'")
            ->whereRaw("NOT EXISTS (
                SELECT 1 
                FROM rep_pvt r2 
                WHERE r2.hn = rep_pvt.hn 
                  AND r2.id != rep_pvt.id
                  AND (r2.error_code IS NULL OR r2.error_code = '' OR r2.error_code = '-' OR r2.error_code REGEXP '(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)')
                  AND (
                      (rep_pvt.rep_type = 'IP' AND r2.an = rep_pvt.an AND r2.rep_type = 'IP')
                      OR
                      (rep_pvt.rep_type = 'OP' AND r2.vstdate = rep_pvt.vstdate AND r2.rep_type = 'OP')
                  )
            )");

        if ($type == 'OP' || $type == 'IP') {
            $query->where('rep_type', $type);
        }

        $result = $query->groupBy('error_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $labels = [];
        $values = [];
        foreach ($result as $row) {
            $labels[] = $row->error_code;
            $values[] = (int) $row->count;
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    public function rep_pvt_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files' => 'required|array|max:5',
            'files.*' => 'file|extensions:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames = [];

        Rep_pvtexcel::truncate();

        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Column mapping list (1-based index from Excel A-BF) for PVT
                $colMapping = [
            1 => 'repno',
            2 => 'no',
            3 => 'tran_id',
            4 => 'hn',
            5 => 'an',
            6 => 'cid',
            7 => 'pt_name',
            8 => 'pt_type',
            // 9 & 10 are dates (handled manually)
            11 => 'net_compensate_nhso', // ชดเชยสุทธิ
            12 => 'net_compensate_employer', // PP
            13 => 'main_fund', // กองทุน
            14 => 'error_code', // Error Code
            15 => 'service_type', // ประเภทบริการ
            16 => 'refer_type', // การรับส่งต่อ
            17 => 'has_right', // การมีสิทธิ
            18 => 'use_right', // การใช้สิทธิ
            19 => 'maininscl', // สิทธิหลัก
            20 => 'subinscl', // สิทธิรอง
            21 => 'href', // HREF
            22 => 'hcode', // HCODE
            23 => 'prov1', // PROV1
            24 => 'hmain', // รหัสหน่วยงาน
            25 => 'prov2', // ชื่อหน่วยงาน
            26 => 'proj', // PROJ
            27 => 'pa', // PA
            28 => 'drg', // DRG
            29 => 'rw', // RW
            30 => 'charge_total', // เรียกเก็บ
            31 => 'charge_non_vehicle_drug_device', // PP charge
            32 => 'charge_vehicle_drug_device', // เบิกได้
            33 => 'charge_central_reimburse', // เบิกไม่ได้
            34 => 'self_pay', // ชำระเอง
            35 => 'payrate_point', // อัตราจ่าย
            36 => 'delay_ps', // ล่าช้า (PS)
            37 => 'delay_percent', // ล่าช้า (PS) เปอร์เซ็นต์
            38 => 'ccuf', // CCUF
            39 => 'adjrw_nhso', // AdjRW
            40 => 'act_amount', // พรบ.
            49 => 'deny_ip', // Deny IP
            50 => 'deny_hc', // Deny OP
            51 => 'deny_ae', // Deny PALG
            52 => 'deny_inst', // Deny INST
            53 => 'deny_dmis', // Deny OT
            54 => 'pay_pattern', // ORS
            55 => 'va', // VA
            56 => 'audit_results', // AUDIT RESULTS
            57 => 'seq_no', // SEQ NO
            58 => 'invoice_no', // INVOICE NO
            59 => 'invoice_lt' // INVOICE LT
        ];

        foreach ($uploadedFiles as $file) {
            $file_name = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            // Determine if IP or OP from filename (eclaim_10989_OPPVT... or IPPVT)
            $rep_type = 'OP';
            if (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPPVT_') !== false) {
                $rep_type = 'IP';
            }

            // Determine if Appeal from filename
            $is_appeal = 0;
            if (stripos($file_name, '_APPEAL_') !== false) {
                $is_appeal = 1;
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();
            $startRow = 8; // PVT e-Claim Excel files data starts at Row 8

            $activeColMapping = $this->detectRepColMapping($sheet, $colMapping);
            $buffer = [];

            for ($row = $startRow; $row <= $row_limit; $row++) {
                $hn = $sheet->getCell('D' . $row)->getValue();
                if (empty($hn)) {
                    continue;
                }

                // Handle admission datetime
                $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                $datetimeadm = null;
                $vstdate = null;
                $vsttime = null;
                if (!empty($rawAdm) && $rawAdm !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                        if ($d) {
                            $datetimeadm = $d->format('Y-m-d H:i:s');
                            $vstdate = $d->format('Y-m-d');
                            $vsttime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        Log::warning("Date parse failed for admin date: " . $rawAdm);
                    }
                }

                // Handle discharge datetime
                $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                $datetimedch = null;
                $dchdate = null;
                $dchtime = null;
                if (!empty($rawDch) && $rawDch !== '-') {
                    try {
                        $d = Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                        if ($d) {
                            $datetimedch = $d->format('Y-m-d H:i:s');
                            $dchdate = $d->format('Y-m-d');
                            $dchtime = $d->format('H:i:s');
                        }
                    } catch (\Exception $e) {
                        try {
                            $d = Carbon::createFromFormat('d/m/Y', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d 00:00:00');
                                $dchdate = $d->format('Y-m-d');
                            }
                        } catch (\Exception $e2) {
                            Log::warning("Date parse failed for discharge date: " . $rawDch);
                        }
                    }
                }

                $record = [
                    'rep_filename' => $file_name,
                    'rep_type' => $rep_type,
                    'is_appeal' => $is_appeal,
                    'datetimeadm' => $datetimeadm,
                    'vstdate' => $vstdate,
                    'vsttime' => $vsttime,
                    'datetimedch' => $datetimedch,
                    'dchdate' => $dchdate,
                    'dchtime' => $dchtime,
                ];

                foreach ($activeColMapping as $idx => $field) {
                    $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
                    $val = $sheet->getCell($colStr . $row)->getValue();
                    
                    if (in_array($field, $numericFields)) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            $val = 0.00;
                        } else {
                            $val = str_replace(',', '', $val);
                            $val = is_numeric($val) ? (float) $val : 0.00;
                        }
                    } else {
                        if ($val === '-' || $val === null) {
                            $val = '';
                        } else {
                            $trimmedVal = trim((string)$val);
                            if ($field === 'error_code') {
                                $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                    $val = '';
                                } else {
                                    $val = $trimmedVal;
                                }
                            } else {
                                $val = $trimmedVal;
                            }
                        }
                    }
                    $record[$field] = $val;
                }

                $buffer[] = $record;

                if (count($buffer) >= 250) {
                    Rep_pvtexcel::insert($buffer);
                    $buffer = [];
                }
            }

            if (!empty($buffer)) {
                Rep_pvtexcel::insert($buffer);
            }
        }

        // Merge process
        DB::transaction(function () use ($allFileNames) {
            foreach ($allFileNames as $fName) {
                DB::table('rep_pvt')->where('rep_filename', $fName)->delete();
            }

            Rep_pvtexcel::chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $arr = $row->toArray();
                    unset($arr['id']);
                    $arr['created_at'] = now();
                    $arr['updated_at'] = now();
                    Rep_pvt::updateOrInsert(
                        [
                            'rep_filename' => $arr['rep_filename'],
                            'repno' => $arr['repno'],
                            'hn' => $arr['hn'],
                            'datetimeadm' => $arr['datetimeadm']
                        ],
                        $arr
                    );
                }
            });
        });

        Rep_pvtexcel::truncate();

        // Redirect back to the budget year of the imported file
        $redirectYear = date('Y') + 543;
        if (!empty($allFileNames)) {
            $lastName = end($allFileNames);
            preg_match('/25\d{2}/', $lastName, $matches);
            if (!empty($matches)) {
                $y = (int) $matches[0];
                preg_match('/25\d{2}(\d{2})/', $lastName, $mMatches);
                $m = !empty($mMatches) ? (int)$mMatches[1] : 1;
                $redirectYear = $y + ($m >= 10 ? 1 : 0);
            }
        }

        return redirect()
            ->route('rep_pvt', ['budget_year' => $redirectYear])
            ->with('success', 'นำเข้าไฟล์ REP PVT สำเร็จ!');
    }

    public function rep_pvt_detail(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        if ($request->ajax() || $request->export == 'excel') {
            $type = $request->type;

            $query = DB::table('rep_pvt as r')
                ->select(
                    'r.rep_type as dep', 'r.rep_filename', 'r.repno', 'r.hn', 'r.an', 'r.pt_name',
                    'r.datetimeadm', 'r.datetimedch', 'r.proj', 'r.drg', 'r.rw', 'r.charge_total',
                    'r.net_compensate_nhso', 'r.net_compensate_employer', 'r.compensate_from',
                    'r.error_code', 'r.deny_hc', 'r.deny_ae', 'r.deny_inst', 'r.deny_ip', 'r.deny_dmis',
                    'r.remark', 'r.audit_results', 'r.pay_pattern', 'r.invoice_no'
                )
                ->where('r.rep_type', strtoupper($type));

            if ($type == 'opd') {
                $query->whereBetween('r.vstdate', [$start_date, $end_date]);
            } else {
                $query->whereBetween('r.dchdate', [$start_date, $end_date]);
            }

            if ($type == 'opd') {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimeadm');
            } else {
                $query->groupBy('rep_filename', 'repno', 'hn', 'datetimedch');
            }

            if ($request->export == 'excel') {
                $data = $query->orderBy('dep', 'desc')->orderBy('repno')->get();

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('REP_PVT_Detail_' . strtoupper($type));

                $headers = [
                    'ประเภท', 'ชื่อไฟล์ REP', 'เลขที่ REP', 'HN', 'AN', 'ชื่อ-สกุล', 'วันเข้ารับบริการ', 
                    'วันจำหน่าย', 'โครงการ', 'DRG', 'RW', 'ยอดเรียกเก็บ', 'ชดเชย สปสช.', 'ชดเชย ต้นสังกัด', 
                    'ชดเชยจาก', 'Error Code', 'Deny HC', 'Deny AE', 'Deny INST', 'Deny IP', 'Deny DMIS', 
                    'Remark', 'Audit Results', 'รูปแบบการจ่าย', 'เลขที่ Invoice'
                ];

                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }

                $rowNum = 2;
                foreach ($data as $row) {
                    $sheet->setCellValue('A' . $rowNum, $row->dep);
                    $sheet->setCellValue('B' . $rowNum, $row->rep_filename);
                    $sheet->setCellValue('C' . $rowNum, $row->repno);
                    $sheet->setCellValue('D' . $rowNum, $row->hn);
                    $sheet->setCellValue('E' . $rowNum, $row->an);
                    $sheet->setCellValue('F' . $rowNum, $row->pt_name);
                    $sheet->setCellValue('G' . $rowNum, $row->datetimeadm);
                    $sheet->setCellValue('H' . $rowNum, $row->datetimedch);
                    $sheet->setCellValue('I' . $rowNum, $row->proj);
                    $sheet->setCellValue('J' . $rowNum, $row->drg);
                    $sheet->setCellValue('K' . $rowNum, $row->rw);
                    $sheet->setCellValue('L' . $rowNum, $row->charge_total);
                    $sheet->setCellValue('M' . $rowNum, $row->net_compensate_nhso);
                    $sheet->setCellValue('N' . $rowNum, $row->net_compensate_employer);
                    $sheet->setCellValue('O' . $rowNum, $row->compensate_from);
                    $sheet->setCellValue('P' . $rowNum, $row->error_code);
                    $sheet->setCellValue('Q' . $rowNum, $row->deny_hc);
                    $sheet->setCellValue('R' . $rowNum, $row->deny_ae);
                    $sheet->setCellValue('S' . $rowNum, $row->deny_inst);
                    $sheet->setCellValue('T' . $rowNum, $row->deny_ip);
                    $sheet->setCellValue('U' . $rowNum, $row->deny_dmis);
                    $sheet->setCellValue('V' . $rowNum, $row->remark);
                    $sheet->setCellValue('W' . $rowNum, $row->audit_results);
                    $sheet->setCellValue('X' . $rowNum, $row->pay_pattern);
                    $sheet->setCellValue('Y' . $rowNum, $row->invoice_no);
                    $rowNum++;
                }

                foreach (range('A', 'Y') as $columnId) {
                    $sheet->getColumnDimension($columnId)->setAutoSize(true);
                }

                $fileName = 'REP_PVT_Detail_' . strtoupper($type) . '_' . date('Ymd_His') . '.xlsx';
                $tempFile = tempnam(sys_get_temp_dir(), 'excel');
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($tempFile);

                return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            }

            $totalData = $query->count();
            $limit = $request->input('length', 10);
            $start = $request->input('start', 0);

            if ($type == 'opd') {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'charge_total',
                    9 => 'net_compensate_nhso', 10 => 'net_compensate_employer', 11 => 'error_code',
                    12 => 'deny_hc', 13 => 'deny_ae', 14 => 'deny_inst', 15 => 'deny_ip',
                    16 => 'deny_dmis', 17 => 'remark', 18 => 'audit_results'
                ];
            } else {
                $columns = [
                    0 => 'rep_type', 1 => 'repno', 2 => 'hn', 3 => 'an', 4 => 'pt_name',
                    5 => 'datetimeadm', 6 => 'datetimedch', 7 => 'proj', 8 => 'drg', 9 => 'rw',
                    10 => 'charge_total', 11 => 'net_compensate_nhso', 12 => 'net_compensate_employer',
                    13 => 'error_code', 14 => 'deny_hc', 15 => 'deny_ae', 16 => 'deny_inst',
                    17 => 'deny_ip', 18 => 'deny_dmis', 19 => 'remark', 20 => 'audit_results'
                ];
            }

            $orderCol = $columns[$request->input('order.0.column', 0)];
            $orderDir = $request->input('order.0.dir', 'asc');

            $query->orderBy($orderCol, $orderDir);
            $query->offset($start)->limit($limit);

            $posts = $query->get();

            $data = [];
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $nestedData['dep'] = $post->dep;
                    $nestedData['rep_filename'] = $post->rep_filename;
                    $nestedData['repno'] = $post->repno;
                    $nestedData['hn'] = $post->hn;
                    $nestedData['an'] = $post->an ?: '-';
                    $nestedData['pt_name'] = $post->pt_name;
                    $nestedData['datetimeadm'] = $post->datetimeadm ? date('d/m/Y H:i:s', strtotime($post->datetimeadm)) : '-';
                    $nestedData['datetimedch'] = $post->datetimedch ? date('d/m/Y H:i:s', strtotime($post->datetimedch)) : '-';
                    $nestedData['proj'] = $post->proj ?: '-';
                    $nestedData['drg'] = $post->drg ?: '-';
                    $nestedData['rw'] = $post->rw ?: '-';
                    $nestedData['charge_total'] = number_format($post->charge_total, 2);
                    $nestedData['net_compensate_nhso'] = number_format($post->net_compensate_nhso, 2);
                    $nestedData['net_compensate_employer'] = number_format($post->net_compensate_employer, 2);
                    $nestedData['compensate_from'] = $post->compensate_from ?: '-';
                    $nestedData['error_code'] = $post->error_code ?: '-';
                    $nestedData['deny_hc'] = $post->deny_hc ?: '-';
                    $nestedData['deny_ae'] = $post->deny_ae ?: '-';
                    $nestedData['deny_inst'] = $post->deny_inst ?: '-';
                    $nestedData['deny_ip'] = $post->deny_ip ?: '-';
                    $nestedData['deny_dmis'] = $post->deny_dmis ?: '-';
                    $nestedData['remark'] = $post->remark ?: '-';
                    $nestedData['audit_results'] = $post->audit_results ?: '-';
                    $nestedData['pay_pattern'] = $post->pay_pattern ?: '-';
                    $nestedData['invoice_no'] = $post->invoice_no ?: '-';
                    $data[] = $nestedData;
                }
            }

            return response()->json([
                "draw"            => intval($request->input('draw')),
                "recordsTotal"    => intval($totalData),
                "recordsFiltered" => intval($totalData),
                "data"            => $data
            ]);
        }

        return view('import.rep_pvt_detail', compact('start_date', 'end_date'));
    }

    public function rep_pvt_detail_opd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_pvt_detail_opd', compact('start_date', 'end_date'));
    }

    public function rep_pvt_detail_ipd(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));
        return view('import.rep_pvt_detail_ipd', compact('start_date', 'end_date'));
    }

    public function deleteBatch(Request $request)
    {
        if (auth()->user()->status !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการลบข้อมูลนี้ (เฉพาะ Admin เท่านั้น)',
            ], 403);
        }

        $request->validate([
            'type' => 'required|string',
            'filename' => 'required|string',
        ]);

        $type = $request->type;
        $filename = $request->filename;

        $map = [
            'rep_ucs' => 'rep_ucs',
            'rep_ofc' => 'rep_ofc',
            'rep_sss' => 'rep_sss',
            'rep_lgo' => 'rep_lgo',
            'rep_bkk' => 'rep_bkk',
            'rep_bmt' => 'rep_bmt',
            'rep_srt' => 'rep_srt',
            'rep_pvt' => 'rep_pvt',
        ];

        if (!isset($map[$type])) {
            return response()->json([
                'status' => 'error',
                'message' => 'ประเภทข้อมูลนำเข้า REP ไม่ถูกต้อง',
            ], 400);
        }

        $table = $map[$type];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $deletedCount = DB::table($table)->where('rep_filename', $filename)->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'ลบข้อมูลนำเข้าสำเร็จ จำนวน ' . number_format($deletedCount) . ' รายการ',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => "ไม่พบตาราง {$table} ในระบบ",
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage(),
            ], 500);
        }
    }

}
