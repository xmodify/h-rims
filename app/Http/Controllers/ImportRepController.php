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
            SUM(CASE WHEN error_code IS NULL OR error_code = '' THEN 1 ELSE 0 END) AS count_pass,
            SUM(CASE WHEN error_code IS NOT NULL AND error_code != '' THEN 1 ELSE 0 END) AS count_fail,
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
                      AND (r2.error_code IS NULL OR r2.error_code = '')
                      AND (
                          (r.rep_type = 'IP' AND r2.an = r.an AND r2.rep_type = 'IP')
                          OR
                          (r.rep_type = 'OP' AND r2.vstdate = r.vstdate AND r2.rep_type = 'OP')
                      )
                    LIMIT 1
                ) as resolved_repno"),
                DB::raw("(
                    SELECT s.stm_filename 
                    FROM stm_ucs s 
                    WHERE s.hn = r.hn 
                      AND (
                          (r.rep_type = 'IP' AND s.an = r.an)
                          OR
                          (r.rep_type = 'OP' AND s.vstdate = r.vstdate)
                      )
                    LIMIT 1
                ) as statement_file")
            ])
            ->get();

        // Format dates, numbers, and resolution status badges
        $formattedPatients = [];
        foreach ($patients as $p) {
            $service_date = ($type == 'OP') ? $p->vstdate : $p->dchdate;

            // Determine status
            if ($p->statement_file) {
                $status_text = 'ผ่านใน STM: ' . $p->statement_file;
                $status_color = 'success';
            } elseif ($p->resolved_repno) {
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
            ->where('error_code', '!=', '');

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

                // Parse 120 columns using index
                for ($c = 1; $c <= 120; $c++) {
                    if ($c === 9 || $c === 10) {
                        continue; // Date fields handled above
                    }

                    $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $val = $sheet->getCell($colChar . $row)->getValue();
                    $fieldName = $colMapping[$c];

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
                            $rowData[$fieldName] = trim((string)$val);
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

        return redirect()
            ->route('rep_ucs')
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
}
