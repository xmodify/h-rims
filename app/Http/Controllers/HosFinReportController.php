<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HosFinReportController extends Controller
{
    /**
     * Account definitions based on MOC standard (Image 2)
     */
    public static function getAccountDefinitions(): array
    {
        return [
            '11' => ['name' => 'จำนวนครั้งของผู้ป่วยนอก UC/เดือน', 'type' => 'monthly', 'unit' => 'ครั้ง'],
            '12' => ['name' => 'จำนวนครั้งของผู้ป่วยนอกประกันสังคม/เดือน', 'type' => 'monthly', 'unit' => 'ครั้ง'],
            '13' => ['name' => 'จำนวนครั้งของผู้ป่วยนอกข้าราชการ/เดือน', 'type' => 'monthly', 'unit' => 'ครั้ง'],
            '14' => ['name' => 'จำนวนครั้งของผู้ป่วยนอก อปท/เดือน', 'type' => 'monthly', 'unit' => 'ครั้ง'],
            '15' => ['name' => 'จำนวนครั้งของผู้ป่วยนอกทั้งหมด/เดือน', 'type' => 'monthly', 'unit' => 'ครั้ง'],
            '16' => ['name' => 'จำนวนผู้ป่วยนอก (HN) ที่มารับบริการ/ไตรมาส', 'type' => 'quarterly', 'unit' => 'คน (HN)'],
            '21' => ['name' => 'จำนวนผู้ป่วยใน UC/เดือน', 'type' => 'monthly', 'unit' => 'ราย'],
            '22' => ['name' => 'จำนวนผู้ป่วยในประกันสังคม/เดือน', 'type' => 'monthly', 'unit' => 'ราย'],
            '23' => ['name' => 'จำนวนผู้ป่วยในข้าราชการ/เดือน', 'type' => 'monthly', 'unit' => 'ราย'],
            '24' => ['name' => 'จำนวนผู้ป่วยใน อปท/เดือน', 'type' => 'monthly', 'unit' => 'ราย'],
            '25' => ['name' => 'จำนวนผู้ป่วยในทั้งหมด/เดือน', 'type' => 'monthly', 'unit' => 'ราย'],
            '31' => ['name' => 'จำนวนวันนอนทั้งหมด/เดือน', 'type' => 'monthly', 'unit' => 'วัน'],
            '32' => ['name' => 'จำนวนผู้ป่วยที่เป็นโรคเบาหวาน-ความดัน/ไตรมาส', 'type' => 'quarterly', 'unit' => 'คน'],
            '41' => ['name' => 'จำนวน SumAdjRW ผู้ป่วย UC/เดือน', 'type' => 'monthly', 'unit' => 'SumAdjRW'],
            '42' => ['name' => 'จำนวน SumAdjRW ผู้ป่วยประกันสังคม/เดือน', 'type' => 'monthly', 'unit' => 'SumAdjRW'],
            '43' => ['name' => 'จำนวน SumAdjRW ผู้ป่วยข้าราชการ/เดือน', 'type' => 'monthly', 'unit' => 'SumAdjRW'],
            '44' => ['name' => 'จำนวน SumAdjRW ผู้ป่วย อปท/เดือน', 'type' => 'monthly', 'unit' => 'SumAdjRW'],
            '45' => ['name' => 'จำนวน SumAdjRW ทั้งหมด/เดือน', 'type' => 'monthly', 'unit' => 'SumAdjRW'],
        ];
    }

    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_hosfin !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ข้อมูลบัญชีหน่วยงาน (HosFin)'], 403);
                }
                return $next($request);
            }
        ]);
    }

    /**
     * Reports Hub Index Page
     */
    public function index(Request $request)
    {
        $accounts = self::getAccountDefinitions();

        $currentMonth = intval(date('n'));
        $currentYear = intval(date('Y'));
        $thaiYear = $currentYear + 543;
        $budgetYear = ($currentMonth >= 10) ? ($thaiYear + 1) : $thaiYear;

        // Current quarter
        if ($currentMonth >= 10 && $currentMonth <= 12) {
            $quarter = 1;
        } elseif ($currentMonth >= 1 && $currentMonth <= 3) {
            $quarter = 2;
        } elseif ($currentMonth >= 4 && $currentMonth <= 6) {
            $quarter = 3;
        } else {
            $quarter = 4;
        }

        $defaultStartDate = date('Y-m-01');
        $defaultEndDate = date('Y-m-t');
        $quarterDates = $this->calculateQuarterDates($budgetYear, $quarter);

        $budgetYearChoices = [
            $budgetYear + 1,
            $budgetYear,
            $budgetYear - 1,
            $budgetYear - 2,
            $budgetYear - 3,
        ];

        $hfaServiceDefs = HfaReportController::getServiceDefinitions();

        return view('hosfin.reports.index', compact(
            'accounts',
            'hfaServiceDefs',
            'budgetYear',
            'budgetYearChoices',
            'quarter',
            'defaultStartDate',
            'defaultEndDate',
            'quarterDates'
        ));
    }

    /**
     * Calculate quarter start and end dates based on Thai budget year and quarter (1-4)
     */
    private function calculateQuarterDates(int $budgetYear, int $quarter): array
    {
        $ceYear = $budgetYear - 543;
        switch ($quarter) {
            case 1:
                return [
                    'start' => ($ceYear - 1) . '-10-01',
                    'end'   => ($ceYear - 1) . '-12-31',
                    'label' => "ไตรมาส 1 (1 ต.ค. - 31 ธ.ค. " . ($budgetYear - 1) . ")"
                ];
            case 2:
                return [
                    'start' => $ceYear . '-01-01',
                    'end'   => $ceYear . '-03-31',
                    'label' => "ไตรมาส 2 (1 ม.ค. - 31 มี.ค. " . $budgetYear . ")"
                ];
            case 3:
                return [
                    'start' => $ceYear . '-04-01',
                    'end'   => $ceYear . '-06-30',
                    'label' => "ไตรมาส 3 (1 เม.ย. - 30 มิ.ย. " . $budgetYear . ")"
                ];
            case 4:
            default:
                return [
                    'start' => $ceYear . '-07-01',
                    'end'   => $ceYear . '-09-30',
                    'label' => "ไตรมาส 4 (1 ก.ค. - 30 ก.ย. " . $budgetYear . ")"
                ];
        }
    }

    /**
     * Fetch and Process live service data directly from HOSxP
     */
    public function processServiceData(Request $request)
    {
        ini_set('max_execution_time', 180);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $budgetYear = intval($request->input('budget_year', HosFinController::getCurrentBudgetYear()));
        $quarter = intval($request->input('quarter', 1));

        if (!$startDate || !$endDate) {
            return response()->json(['success' => false, 'message' => 'กรุณาระบุช่วงวันที่เริ่มต้นและสิ้นสุด'], 422);
        }

        $qDates = $this->calculateQuarterDates($budgetYear, $quarter);
        $qStart = $request->input('quarter_start') ?: $qDates['start'];
        $qEnd = $request->input('quarter_end') ?: $qDates['end'];

        try {
            // 1. OPD Statistics for the Monthly Date Range (Matching claim_op logic & visit_pttype)
            $opdStats = DB::connection('hosxp')->selectOne("
                SELECT 
                    COUNT(vn) as total_opd,
                    COUNT(CASE WHEN hipdata_code IN ('UCS','WEL') THEN vn END) as ucs_count,
                    COUNT(CASE WHEN hipdata_code IN ('SSS','SSI') THEN vn END) as sss_count,
                    COUNT(CASE WHEN hipdata_code = 'OFC' THEN vn END) as ofc_count,
                    COUNT(CASE WHEN hipdata_code = 'LGO' THEN vn END) as lgo_count
                FROM (
                    SELECT o.vn, p.hipdata_code
                    FROM ovst o
                    LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                    LEFT JOIN pttype p ON p.pttype = IFNULL(vp.pttype, o.pttype)
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an = '' OR o.an IS NULL)
                    GROUP BY o.vn
                ) as a
            ", [$startDate, $endDate]);

            // 2. OPD Unique HN for the Quarter
            $opdHnQuarter = DB::connection('hosxp')->table('ovst as o')
                ->whereBetween('o.vstdate', [$qStart, $qEnd])
                ->where(function($q) {
                    $q->whereNull('o.an')->orWhere('o.an', '');
                })
                ->distinct()
                ->count('o.hn');

            // 3. IPD Statistics for the Monthly Date Range (Matching /emr/ipd/ipd_visit & claim_ip)
            $ipdStats = DB::connection('hosxp')->selectOne("
                SELECT 
                    COUNT(an) AS total_ipd,
                    COUNT(CASE WHEN hipdata_code IN ('UCS','WEL') THEN an END) as ucs_ipd,
                    COUNT(CASE WHEN hipdata_code IN ('SSS','SSI') THEN an END) as sss_ipd,
                    COUNT(CASE WHEN hipdata_code = 'OFC' THEN an END) as ofc_ipd,
                    COUNT(CASE WHEN hipdata_code = 'LGO' THEN an END) as lgo_ipd,
                    SUM(admdate) as total_days,
                    SUM(adjrw) as total_adjrw,
                    SUM(CASE WHEN hipdata_code IN ('UCS','WEL') THEN adjrw ELSE 0 END) as ucs_adjrw,
                    SUM(CASE WHEN hipdata_code IN ('SSS','SSI') THEN adjrw ELSE 0 END) as sss_adjrw,
                    SUM(CASE WHEN hipdata_code = 'OFC' THEN adjrw ELSE 0 END) as ofc_adjrw,
                    SUM(CASE WHEN hipdata_code = 'LGO' THEN adjrw ELSE 0 END) as lgo_adjrw
                FROM (
                    SELECT i.an, p.hipdata_code, COALESCE(a.admdate, 0) as admdate, COALESCE(i.adjrw, 0) as adjrw
                    FROM ipt i
                    LEFT JOIN ipt_pttype ip ON ip.an = i.an
                    LEFT JOIN pttype p ON p.pttype = IFNULL(ip.pttype, i.pttype)
                    LEFT JOIN an_stat a ON a.an = i.an
                    WHERE i.dchdate BETWEEN ? AND ?
                      AND i.confirm_discharge = 'Y'
                    GROUP BY i.an
                ) as a
            ", [$startDate, $endDate]);

            // 4. Diabetes (DM) and Hypertension (HT) Patients in Quarter
            $dmhtPatients = DB::connection('hosxp')->table('ovst as o')
                ->whereBetween('o.vstdate', [$qStart, $qEnd])
                ->where(function($q) {
                    $q->whereExists(function($sub) {
                        $sub->select(DB::raw(1))
                            ->from('ovstdiag as od')
                            ->whereColumn('od.vn', 'o.vn')
                            ->where(function($w) {
                                $w->where('od.icd10', 'like', 'E10%')
                                  ->orWhere('od.icd10', 'like', 'E11%')
                                  ->orWhere('od.icd10', 'like', 'E12%')
                                  ->orWhere('od.icd10', 'like', 'E13%')
                                  ->orWhere('od.icd10', 'like', 'E14%')
                                  ->orWhere('od.icd10', 'like', 'I10%')
                                  ->orWhere('od.icd10', 'like', 'I11%')
                                  ->orWhere('od.icd10', 'like', 'I12%')
                                  ->orWhere('od.icd10', 'like', 'I13%')
                                  ->orWhere('od.icd10', 'like', 'I15%');
                            });
                    })
                    ->orWhereExists(function($sub) {
                        $sub->select(DB::raw(1))
                            ->from('clinicmember as cm')
                            ->whereColumn('cm.hn', 'o.hn')
                            ->whereIn('cm.clinic', ['001', '002']);
                    });
                })
                ->distinct()
                ->count('o.hn');

            $thaiMonthsShort = [
                1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
            ];
            $fetchDateTime = date('j') . ' ' . $thaiMonthsShort[intval(date('n'))] . ' ' . (intval(date('Y')) + 543) . ' เวลา ' . date('H:i:s') . ' น.';
            $importDate = date('d/m/') . (intval(date('Y')) + 543) . ' ' . date('H:i');

            // Construct values mapping
            $results = [
                '11' => intval($opdStats->ucs_count ?? 0),
                '12' => intval($opdStats->sss_count ?? 0),
                '13' => intval($opdStats->ofc_count ?? 0),
                '14' => intval($opdStats->lgo_count ?? 0),
                '15' => intval($opdStats->total_opd ?? 0),
                '16' => intval($opdHnQuarter ?? 0),
                '21' => intval($ipdStats->ucs_ipd ?? 0),
                '22' => intval($ipdStats->sss_ipd ?? 0),
                '23' => intval($ipdStats->ofc_ipd ?? 0),
                '24' => intval($ipdStats->lgo_ipd ?? 0),
                '25' => intval($ipdStats->total_ipd ?? 0),
                '31' => intval($ipdStats->total_days ?? 0),
                '32' => intval($dmhtPatients ?? 0),
                '41' => round(floatval($ipdStats->ucs_adjrw ?? 0), 4),
                '42' => round(floatval($ipdStats->sss_adjrw ?? 0), 4),
                '43' => round(floatval($ipdStats->ofc_adjrw ?? 0), 4),
                '44' => round(floatval($ipdStats->lgo_adjrw ?? 0), 4),
                '45' => round(floatval($ipdStats->total_adjrw ?? 0), 4),
            ];

            $now = Carbon::now('Asia/Bangkok');
            $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            $buddhistYear = $now->year + 543;
            $fetchDateTime = $now->day . ' ' . $thaiMonths[$now->month] . ' ' . $buddhistYear . ' เวลา ' . $now->format('H:i:s') . ' น.';
            $importDate = $now->format('Y-m-d H:i:s');

            return response()->json([
                'success' => true,
                'message' => 'ดึงข้อมูลจาก HOSxP สำเร็จ',
                'data' => $results,
                'fetch_datetime' => $fetchDateTime,
                'import_date' => $importDate,
                'summary' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'quarter_start' => $qStart,
                    'quarter_end' => $qEnd,
                    'quarter_label' => $qDates['label'] ?? "ไตรมาส {$quarter}"
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("Error processing service data from HOSxP: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อหรือประมวลผล HOSxP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Service Data to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));
        $items = $request->input('items', []);

        $definitions = self::getAccountDefinitions();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ข้อมูลบริการประกอบงบ');

        // Headers (3 columns: รหัสบัญชี, ชื่อบัญชี, จำนวน)
        $sheet->setCellValue('A1', 'รหัสบัญชี');
        $sheet->setCellValue('B1', 'ชื่อบัญชี');
        $sheet->setCellValue('C1', 'จำนวน');

        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '336699'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0C4DE']],
            ],
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $rowIdx = 2;
        foreach ($definitions as $code => $def) {
            $val = isset($items[$code]) ? floatval(str_replace(',', '', $items[$code])) : 0;

            $sheet->setCellValueExplicit('A' . $rowIdx, $code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowIdx, $def['name']);
            $sheet->setCellValue('C' . $rowIdx, $val);

            // Row styling
            $bgColor = ($rowIdx % 2 == 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$rowIdx}:C{$rowIdx}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Number format
            if (str_starts_with($code, '4')) {
                $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0.0000');
            } else {
                $sheet->getStyle('C' . $rowIdx)->getNumberFormat()->setFormatCode('#,##0');
            }

            $sheet->getRowDimension($rowIdx)->setRowHeight(24);
            $rowIdx++;
        }

        // Auto-fit column widths
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(55);
        $sheet->getColumnDimension('C')->setWidth(22);

        $filename = "service_data_report_{$startDate}_to_{$endDate}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
