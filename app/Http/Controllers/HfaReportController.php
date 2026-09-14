<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HfaReportController extends Controller
{
    /**
     * 54 HFA Service Definitions
     */
    public static function getServiceDefinitions(): array
    {
        return [
            // OPV: ผู้ป่วยนอก (ครั้ง/เดือน)
            'OPV_01' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'UC', 'label' => 'ผู้ป่วยนอก UC (ครั้ง)'],
            'OPV_02' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'SSS', 'label' => 'ผู้ป่วยนอก ประกันสังคม (ครั้ง)'],
            'OPV_03' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'OFC', 'label' => 'ผู้ป่วยนอก ข้าราชการ (ครั้ง)'],
            'OPV_04' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'LGO', 'label' => 'ผู้ป่วยนอก อปท. (ครั้ง)'],
            'OPV_05' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'STP', 'label' => 'ผู้ป่วยนอก บุคคลไร้สิทธิ (ครั้ง)'],
            'OPV_06' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'FWF', 'label' => 'ผู้ป่วยนอก แรงงานต่างด้าว (ครั้ง)'],
            'OPV_07' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'ชำระเงินเอง', 'label' => 'ผู้ป่วยนอก ชำระเงินเอง (ครั้ง)'],
            'OPV_08' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'สิทธิอื่นๆ', 'label' => 'ผู้ป่วยนอก สิทธิอื่นๆ (ครั้ง)'],
            'OPV_09' => ['type' => 'OPD', 'item' => 'Visit (ครั้ง/เดือน)', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'ผู้ป่วยนอก รวมทุกสิทธิ (ครั้ง)'],

            // OPH: ผู้ป่วยนอก (คน/เดือน - HN)
            'OPH_01' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'UC', 'label' => 'ผู้ป่วยนอก UC (คน/HN)'],
            'OPH_02' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'SSS', 'label' => 'ผู้ป่วยนอก ประกันสังคม (คน/HN)'],
            'OPH_03' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'OFC', 'label' => 'ผู้ป่วยนอก ข้าราชการ (คน/HN)'],
            'OPH_04' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'LGO', 'label' => 'ผู้ป่วยนอก อปท. (คน/HN)'],
            'OPH_05' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'STP', 'label' => 'ผู้ป่วยนอก บุคคลไร้สิทธิ (คน/HN)'],
            'OPH_06' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'FWF', 'label' => 'ผู้ป่วยนอก แรงงานต่างด้าว (คน/HN)'],
            'OPH_07' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'ชำระเงินเอง', 'label' => 'ผู้ป่วยนอก ชำระเงินเอง (คน/HN)'],
            'OPH_08' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'สิทธิอื่นๆ', 'label' => 'ผู้ป่วยนอก สิทธิอื่นๆ (คน/HN)'],
            'OPH_09' => ['type' => 'OPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'ผู้ป่วยนอก รวมทุกสิทธิ (คน/HN)'],

            // IPA: ผู้ป่วยใน (ครั้ง/เดือน - Admit)
            'IPA_01' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'UC', 'label' => 'ผู้ป่วยใน UC (ครั้ง)'],
            'IPA_02' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'SSS', 'label' => 'ผู้ป่วยใน ประกันสังคม (ครั้ง)'],
            'IPA_03' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'OFC', 'label' => 'ผู้ป่วยใน ข้าราชการ (ครั้ง)'],
            'IPA_04' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'LGO', 'label' => 'ผู้ป่วยใน อปท. (ครั้ง)'],
            'IPA_05' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'STP', 'label' => 'ผู้ป่วยใน บุคคลไร้สิทธิ (ครั้ง)'],
            'IPA_06' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'FWF', 'label' => 'ผู้ป่วยใน แรงงานต่างด้าว (ครั้ง)'],
            'IPA_07' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'ชำระเงินเอง', 'label' => 'ผู้ป่วยใน ชำระเงินเอง (ครั้ง)'],
            'IPA_08' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'สิทธิอื่นๆ', 'label' => 'ผู้ป่วยใน สิทธิอื่นๆ (ครั้ง)'],
            'IPA_09' => ['type' => 'IPD', 'item' => 'Admit (ครั้ง/เดือน)', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'ผู้ป่วยใน รวมทุกสิทธิ (ครั้ง)'],

            // IPH: ผู้ป่วยใน (คน/เดือน - HN)
            'IPH_01' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'UC', 'label' => 'ผู้ป่วยใน UC (คน/HN)'],
            'IPH_02' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'SSS', 'label' => 'ผู้ป่วยใน ประกันสังคม (คน/HN)'],
            'IPH_03' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'OFC', 'label' => 'ผู้ป่วยใน ข้าราชการ (คน/HN)'],
            'IPH_04' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'LGO', 'label' => 'ผู้ป่วยใน อปท. (คน/HN)'],
            'IPH_05' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'STP', 'label' => 'ผู้ป่วยใน บุคคลไร้สิทธิ (คน/HN)'],
            'IPH_06' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'FWF', 'label' => 'ผู้ป่วยใน แรงงานต่างด้าว (คน/HN)'],
            'IPH_07' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'ชำระเงินเอง', 'label' => 'ผู้ป่วยใน ชำระเงินเอง (คน/HN)'],
            'IPH_08' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'สิทธิอื่นๆ', 'label' => 'ผู้ป่วยใน สิทธิอื่นๆ (คน/HN)'],
            'IPH_09' => ['type' => 'IPD', 'item' => 'HN (คน/เดือน)', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'ผู้ป่วยใน รวมทุกสิทธิ (คน/HN)'],

            // IPS: SumAdjRW/เดือน
            'IPS_01' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'UC', 'label' => 'SumAdjRW UC'],
            'IPS_02' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'SSS', 'label' => 'SumAdjRW ประกันสังคม'],
            'IPS_03' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'OFC', 'label' => 'SumAdjRW ข้าราชการ'],
            'IPS_04' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'LGO', 'label' => 'SumAdjRW อปท.'],
            'IPS_05' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'STP', 'label' => 'SumAdjRW บุคคลไร้สิทธิ'],
            'IPS_06' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'FWF', 'label' => 'SumAdjRW แรงงานต่างด้าว'],
            'IPS_07' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'ชำระเงินเอง', 'label' => 'SumAdjRW ชำระเงินเอง'],
            'IPS_08' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'สิทธิอื่นๆ', 'label' => 'SumAdjRW สิทธิอื่นๆ'],
            'IPS_09' => ['type' => 'IPD', 'item' => 'SumAdjRW/เดือน', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'SumAdjRW รวมทุกสิทธิ'],

            // IPL: Los/เดือน (วันนอน)
            'IPL_01' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'UC', 'label' => 'วันนอน UC (วัน)'],
            'IPL_02' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'SSS', 'label' => 'วันนอน ประกันสังคม (วัน)'],
            'IPL_03' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'OFC', 'label' => 'วันนอน ข้าราชการ (วัน)'],
            'IPL_04' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'LGO', 'label' => 'วันนอน อปท. (วัน)'],
            'IPL_05' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'STP', 'label' => 'วันนอน บุคคลไร้สิทธิ (วัน)'],
            'IPL_06' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'FWF', 'label' => 'วันนอน แรงงานต่างด้าว (วัน)'],
            'IPL_07' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'ชำระเงินเอง', 'label' => 'วันนอน ชำระเงินเอง (วัน)'],
            'IPL_08' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'สิทธิอื่นๆ', 'label' => 'วันนอน สิทธิอื่นๆ (วัน)'],
            'IPL_09' => ['type' => 'IPD', 'item' => 'Los/เดือน', 'rights' => 'รวมทั้งหมดทุกสิทธิ', 'label' => 'วันนอน รวมทุกสิทธิ (วัน)'],
        ];
    }

    /**
     * Get Gregorian Date Range for Budget Year & Month (1-12)
     */
    public static function getMonthDateRange(int $budgetYear, int $month): array
    {
        $ceYear = $budgetYear - 543;
        $year = ($month >= 10) ? ($ceYear - 1) : $ceYear;
        $strMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $startDate = "{$year}-{$strMonth}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        return [$startDate, $endDate];
    }

    /**
     * 1. Process 54 HFA Service Data from HOSxP
     */
    public function processServiceData(Request $request)
    {
        ini_set('max_execution_time', 180);

        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));

        list($startDate, $endDate) = self::getMonthDateRange($budgetYear, $month);

        try {
            // OPD Statistics (OPV & OPH)
            $opd = DB::connection('hosxp')->selectOne("
                SELECT
                    -- OPV (Visits)
                    COUNT(CASE WHEN r = 'UC' THEN vn END) as opv_01,
                    COUNT(CASE WHEN r = 'SSS' THEN vn END) as opv_02,
                    COUNT(CASE WHEN r = 'OFC' THEN vn END) as opv_03,
                    COUNT(CASE WHEN r = 'LGO' THEN vn END) as opv_04,
                    COUNT(CASE WHEN r = 'STP' THEN vn END) as opv_05,
                    COUNT(CASE WHEN r = 'FWF' THEN vn END) as opv_06,
                    COUNT(CASE WHEN r = 'PAY' THEN vn END) as opv_07,
                    COUNT(CASE WHEN r = 'OTHER' THEN vn END) as opv_08,
                    COUNT(vn) as opv_09,

                    -- OPH (Distinct HN)
                    COUNT(DISTINCT CASE WHEN r = 'UC' THEN hn END) as oph_01,
                    COUNT(DISTINCT CASE WHEN r = 'SSS' THEN hn END) as oph_02,
                    COUNT(DISTINCT CASE WHEN r = 'OFC' THEN hn END) as oph_03,
                    COUNT(DISTINCT CASE WHEN r = 'LGO' THEN hn END) as oph_04,
                    COUNT(DISTINCT CASE WHEN r = 'STP' THEN hn END) as oph_05,
                    COUNT(DISTINCT CASE WHEN r = 'FWF' THEN hn END) as oph_06,
                    COUNT(DISTINCT CASE WHEN r = 'PAY' THEN hn END) as oph_07,
                    COUNT(DISTINCT CASE WHEN r = 'OTHER' THEN hn END) as oph_08,
                    COUNT(DISTINCT hn) as oph_09
                FROM (
                    SELECT 
                        o.vn, 
                        o.hn,
                        CASE 
                            WHEN p.hipdata_code IN ('UCS', 'WEL') THEN 'UC'
                            WHEN p.hipdata_code IN ('SSS', 'SSI') THEN 'SSS'
                            WHEN p.hipdata_code = 'OFC' THEN 'OFC'
                            WHEN p.hipdata_code = 'LGO' THEN 'LGO'
                            WHEN p.hipdata_code = 'STP' THEN 'STP'
                            WHEN p.hipdata_code IN ('FWF', 'NRD', 'NRH') THEN 'FWF'
                            WHEN (p.paidst IN ('01', '03') OR p.hipdata_code IN ('A1', 'A9')) THEN 'PAY'
                            ELSE 'OTHER'
                        END as r
                    FROM ovst o
                    LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                    LEFT JOIN pttype p ON p.pttype = IFNULL(vp.pttype, o.pttype)
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an = '' OR o.an IS NULL)
                    GROUP BY o.vn
                ) sub
            ", [$startDate, $endDate]);

            // IPD Statistics (IPA, IPH, IPS, IPL)
            $ipd = DB::connection('hosxp')->selectOne("
                SELECT
                    -- IPA (Admit visits)
                    COUNT(CASE WHEN r = 'UC' THEN an END) as ipa_01,
                    COUNT(CASE WHEN r = 'SSS' THEN an END) as ipa_02,
                    COUNT(CASE WHEN r = 'OFC' THEN an END) as ipa_03,
                    COUNT(CASE WHEN r = 'LGO' THEN an END) as ipa_04,
                    COUNT(CASE WHEN r = 'STP' THEN an END) as ipa_05,
                    COUNT(CASE WHEN r = 'FWF' THEN an END) as ipa_06,
                    COUNT(CASE WHEN r = 'PAY' THEN an END) as ipa_07,
                    COUNT(CASE WHEN r = 'OTHER' THEN an END) as ipa_08,
                    COUNT(an) as ipa_09,

                    -- IPH (Distinct HN)
                    COUNT(DISTINCT CASE WHEN r = 'UC' THEN hn END) as iph_01,
                    COUNT(DISTINCT CASE WHEN r = 'SSS' THEN hn END) as iph_02,
                    COUNT(DISTINCT CASE WHEN r = 'OFC' THEN hn END) as iph_03,
                    COUNT(DISTINCT CASE WHEN r = 'LGO' THEN hn END) as iph_04,
                    COUNT(DISTINCT CASE WHEN r = 'STP' THEN hn END) as iph_05,
                    COUNT(DISTINCT CASE WHEN r = 'FWF' THEN hn END) as iph_06,
                    COUNT(DISTINCT CASE WHEN r = 'PAY' THEN hn END) as iph_07,
                    COUNT(DISTINCT CASE WHEN r = 'OTHER' THEN hn END) as iph_08,
                    COUNT(DISTINCT hn) as iph_09,

                    -- IPS (SumAdjRW)
                    SUM(CASE WHEN r = 'UC' THEN adjrw ELSE 0 END) as ips_01,
                    SUM(CASE WHEN r = 'SSS' THEN adjrw ELSE 0 END) as ips_02,
                    SUM(CASE WHEN r = 'OFC' THEN adjrw ELSE 0 END) as ips_03,
                    SUM(CASE WHEN r = 'LGO' THEN adjrw ELSE 0 END) as ips_04,
                    SUM(CASE WHEN r = 'STP' THEN adjrw ELSE 0 END) as ips_05,
                    SUM(CASE WHEN r = 'FWF' THEN adjrw ELSE 0 END) as ips_06,
                    SUM(CASE WHEN r = 'PAY' THEN adjrw ELSE 0 END) as ips_07,
                    SUM(CASE WHEN r = 'OTHER' THEN adjrw ELSE 0 END) as ips_08,
                    SUM(adjrw) as ips_09,

                    -- IPL (LOS / Days)
                    SUM(CASE WHEN r = 'UC' THEN admdate ELSE 0 END) as ipl_01,
                    SUM(CASE WHEN r = 'SSS' THEN admdate ELSE 0 END) as ipl_02,
                    SUM(CASE WHEN r = 'OFC' THEN admdate ELSE 0 END) as ipl_03,
                    SUM(CASE WHEN r = 'LGO' THEN admdate ELSE 0 END) as ipl_04,
                    SUM(CASE WHEN r = 'STP' THEN admdate ELSE 0 END) as ipl_05,
                    SUM(CASE WHEN r = 'FWF' THEN admdate ELSE 0 END) as ipl_06,
                    SUM(CASE WHEN r = 'PAY' THEN admdate ELSE 0 END) as ipl_07,
                    SUM(CASE WHEN r = 'OTHER' THEN admdate ELSE 0 END) as ipl_08,
                    SUM(admdate) as ipl_09
                FROM (
                    SELECT 
                        i.an,
                        i.hn,
                        COALESCE(a.admdate, 0) as admdate,
                        COALESCE(i.adjrw, 0) as adjrw,
                        CASE 
                            WHEN p.hipdata_code IN ('UCS', 'WEL') THEN 'UC'
                            WHEN p.hipdata_code IN ('SSS', 'SSI') THEN 'SSS'
                            WHEN p.hipdata_code = 'OFC' THEN 'OFC'
                            WHEN p.hipdata_code = 'LGO' THEN 'LGO'
                            WHEN p.hipdata_code = 'STP' THEN 'STP'
                            WHEN p.hipdata_code IN ('FWF', 'NRD', 'NRH') THEN 'FWF'
                            WHEN (p.paidst IN ('01', '03') OR p.hipdata_code IN ('A1', 'A9')) THEN 'PAY'
                            ELSE 'OTHER'
                        END as r
                    FROM ipt i
                    LEFT JOIN ipt_pttype ip ON ip.an = i.an
                    LEFT JOIN pttype p ON p.pttype = IFNULL(ip.pttype, i.pttype)
                    LEFT JOIN an_stat a ON a.an = i.an
                    WHERE i.dchdate BETWEEN ? AND ?
                      AND i.confirm_discharge = 'Y'
                    GROUP BY i.an
                ) sub
            ", [$startDate, $endDate]);

            // Combine into 54 items
            $results = [];
            $defs = self::getServiceDefinitions();
            foreach ($defs as $code => $def) {
                $prop = strtolower($code);
                if (str_starts_with($code, 'OP')) {
                    $results[$code] = intval($opd->$prop ?? 0);
                } elseif (str_starts_with($code, 'IP') && !str_starts_with($code, 'IPS')) {
                    $results[$code] = intval($ipd->$prop ?? 0);
                } elseif (str_starts_with($code, 'IPS')) {
                    $results[$code] = round(floatval($ipd->$prop ?? 0), 4);
                } else {
                    $results[$code] = 0;
                }
            }

            $now = Carbon::now('Asia/Bangkok');
            $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            $buddhistYear = $now->year + 543;
            $fetchDateTime = $now->day . ' ' . $thaiMonths[$now->month] . ' ' . $buddhistYear . ' เวลา ' . $now->format('H:i:s') . ' น.';

            return response()->json([
                'success' => true,
                'message' => 'ดึงข้อมูลบริการ HFA จาก HOSxP สำเร็จ',
                'data' => $results,
                'fetch_datetime' => $fetchDateTime,
                'period' => [
                    'fiscal_year' => $budgetYear,
                    'month' => $month,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'month_name' => $thaiMonths[$month] ?? "เดือน {$month}"
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error("HFA Service Data query error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลบริการ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Export HFA Service Data to Excel (.xlsx) matching template_ข้อมูลบริการ.xlsx
     */
    public function exportServiceExcel(Request $request)
    {
        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));
        $items = $request->input('items', []);

        if (empty($items)) {
            // Pull data if not supplied
            $subReq = new Request(['fiscal_year' => $budgetYear, 'month' => $month]);
            $res = $this->processServiceData($subReq);
            $json = $res->getData(true);
            $items = $json['data'] ?? [];
        }

        $templatePath = base_path('docs/template_ข้อมูลบริการ.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Populate Column F (Amount) based on Column B (Code_SerV)
            for ($r = 3; $r <= $highestRow; $r++) {
                $code = trim($sheet->getCell('B' . $r)->getValue());
                if (isset($items[$code])) {
                    $val = $items[$code];
                    $cleanVal = floatval(str_replace(',', '', $val));
                    if (str_starts_with($code, 'IPS')) {
                        $sheet->setCellValue('F' . $r, $cleanVal);
                        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0.0000');
                    } else {
                        $sheet->setCellValue('F' . $r, intval($cleanVal));
                        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');
                    }
                }
            }
        } else {
            // Build fallback spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('RawServ');
            $sheet->setCellValue('B1', 'RawServ (ตารางข้อมูลบริการ)');
            $sheet->setCellValue('B2', 'Code_SerV');
            $sheet->setCellValue('C2', 'SerV_Type');
            $sheet->setCellValue('D2', 'Item');
            $sheet->setCellValue('E2', 'Rights');
            $sheet->setCellValue('F2', 'Amount');

            $defs = self::getServiceDefinitions();
            $r = 3;
            foreach ($defs as $code => $def) {
                $val = floatval(str_replace(',', '', $items[$code] ?? 0));
                $sheet->setCellValue('B' . $r, $code);
                $sheet->setCellValue('C' . $r, $def['type']);
                $sheet->setCellValue('D' . $r, $def['item']);
                $sheet->setCellValue('E' . $r, $def['rights']);
                $sheet->setCellValue('F' . $r, $val);
                $r++;
            }
        }

        $filename = "template_ข้อมูลบริการ_ปี{$budgetYear}_เดือน{$month}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 3. Send HFA Service Data via API (POST /hfa_api/open-api/import/service)
     */
    public function sendServiceApi(Request $request)
    {
        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));
        $items = $request->input('items', []);

        // 1. Get Token from FDH
        $token = $this->getFdhAccessToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถขอ FDH Access Token ได้ กรุณาตรวจสอบการตั้งค่า FDH User / Pass ในโปรไฟล์'
            ], 400);
        }

        // 2. Generate Excel in temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'hfa_serv_') . '.xlsx';
        $templatePath = base_path('docs/template_ข้อมูลบริการ.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            for ($r = 3; $r <= $highestRow; $r++) {
                $code = trim($sheet->getCell('B' . $r)->getValue());
                if (isset($items[$code])) {
                    $val = floatval(str_replace(',', '', $items[$code]));
                    $sheet->setCellValue('F' . $r, $val);
                }
            }
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $defs = self::getServiceDefinitions();
            $r = 3;
            foreach ($defs as $code => $def) {
                $val = floatval(str_replace(',', '', $items[$code] ?? 0));
                $sheet->setCellValue('B' . $r, $code);
                $sheet->setCellValue('F' . $r, $val);
                $r++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // 3. Send API to HFA
        $hfaUrl = config('services.hfa.url', 'https://hfa.one.th') . '/hfa_api/open-api/import/service';
        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 60])
                ->withToken($token)
                ->attach('file', fopen($tempPath, 'r'), "template_ข้อมูลบริการ.xlsx")
                ->post($hfaUrl, [
                    'fiscal_year' => strval($budgetYear),
                    'month'       => strval($month)
                ]);

            @unlink($tempPath);

            $body = $response->json();
            if ($response->successful() && isset($body['status']) && $body['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => $body['message'] ?? 'ส่งข้อมูลบริการ HFA สำเร็จ',
                    'data'    => $body
                ]);
            } else {
                $errorMsg = $body['message_th'] ?? ($body['message'] ?? ($body['error'] ?? 'เกิดข้อผิดพลาดในการส่งข้อมูลไปยัง HFA'));
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'raw'     => $body
                ], $response->status() ?: 400);
            }

        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::error("HFA Send Service API Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ HFA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. Process HFA Trial Balance (งบทดลอง) from hosfin_trial_balance
     */
    public function processTrialBalance(Request $request)
    {
        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));

        // Format period e.g. "2569-08" or "2568-10"
        $periodYear = ($month >= 10) ? ($budgetYear - 1) : $budgetYear;
        $periodMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $targetPeriod = "{$periodYear}-{$periodMonth}";

        $rows = DB::table('hosfin_trial_balance')
            ->where('acc_period', $targetPeriod)
            ->orderBy('account_code')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบข้อมูลงบทดลองในงวด {$targetPeriod} กรุณานำเข้างบทดลองในระบบ HosFin ก่อน",
                'period'  => $targetPeriod,
                'data'    => [],
                'summary' => [
                    'count'    => 0,
                    'sum_dr'   => 0,
                    'sum_cr'   => 0,
                    'net_diff' => 0,
                    'is_balanced' => false
                ]
            ]);
        }

        $sumDr = 0;
        $sumCr = 0;
        $items = [];
        foreach ($rows as $r) {
            $dr = floatval($r->debit_month);
            $cr = floatval($r->credit_month);
            $bf = floatval($r->debit_bf) - floatval($r->credit_bf);
            $cf = floatval($r->debit_net) - floatval($r->credit_net);

            $sumDr += $dr;
            $sumCr += $cr;

            $items[] = [
                'account_code' => $r->account_code,
                'account_name' => $r->account_name,
                'bf'           => $bf,
                'debit'        => $dr,
                'credit'       => $cr,
                'cf'           => $cf
            ];
        }

        $netDiff = round($sumDr - $sumCr, 2);
        $isBalanced = (abs($netDiff) < 0.01);

        $now = Carbon::now('Asia/Bangkok');
        $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $fetchDateTime = $now->day . ' ' . $thaiMonths[$now->month] . ' ' . ($now->year + 543) . ' เวลา ' . $now->format('H:i:s') . ' น.';

        return response()->json([
            'success' => true,
            'message' => "โหลดข้อมูลงบทดลองงวด {$targetPeriod} สำเร็จ (" . count($items) . " บัญชี)",
            'period'  => $targetPeriod,
            'fetch_datetime' => $fetchDateTime,
            'data'    => $items,
            'summary' => [
                'count'       => count($items),
                'sum_dr'      => $sumDr,
                'sum_cr'      => $sumCr,
                'net_diff'    => $netDiff,
                'is_balanced' => $isBalanced
            ]
        ]);
    }

    /**
     * 5. Export HFA Trial Balance to Excel matching template_ข้อมูลการเงิน.xlsx
     */
    public function exportTrialBalanceExcel(Request $request)
    {
        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));

        $periodYear = ($month >= 10) ? ($budgetYear - 1) : $budgetYear;
        $periodMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $targetPeriod = "{$periodYear}-{$periodMonth}";

        $rows = DB::table('hosfin_trial_balance')
            ->where('acc_period', $targetPeriod)
            ->orderBy('account_code')
            ->get();

        $templatePath = base_path('docs/template_ข้อมูลการเงิน.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Map rows by account code
            $tbMap = [];
            foreach ($rows as $r) {
                $tbMap[trim($r->account_code)] = $r;
            }

            for ($r = 2; $r <= $highestRow; $r++) {
                $code = trim($sheet->getCell('A' . $r)->getValue());
                if (isset($tbMap[$code])) {
                    $item = $tbMap[$code];
                    $bf = floatval($item->debit_bf) - floatval($item->credit_bf);
                    $dr = floatval($item->debit_month);
                    $cr = floatval($item->credit_month);
                    $cf = floatval($item->debit_net) - floatval($item->credit_net);

                    $sheet->setCellValue('C' . $r, $bf);
                    $sheet->setCellValue('D' . $r, $dr);
                    $sheet->setCellValue('E' . $r, $cr);
                    $sheet->setCellValue('F' . $r, $cf);
                } else {
                    $sheet->setCellValue('C' . $r, 0.00);
                    $sheet->setCellValue('D' . $r, 0.00);
                    $sheet->setCellValue('E' . $r, 0.00);
                    $sheet->setCellValue('F' . $r, 0.00);
                }
            }
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('TrialBalance');
            $sheet->setCellValue('A1', 'รหัสบัญชี');
            $sheet->setCellValue('B1', 'ชื่อบัญชี');
            $sheet->setCellValue('C1', 'ยอดยกมา');
            $sheet->setCellValue('D1', 'เดบิต');
            $sheet->setCellValue('E1', 'เครดิต');
            $sheet->setCellValue('F1', 'ยอดยกไป');

            $r = 2;
            foreach ($rows as $item) {
                $bf = floatval($item->debit_bf) - floatval($item->credit_bf);
                $dr = floatval($item->debit_month);
                $cr = floatval($item->credit_month);
                $cf = floatval($item->debit_net) - floatval($item->credit_net);

                $sheet->setCellValueExplicit('A' . $r, $item->account_code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $r, $item->account_name);
                $sheet->setCellValue('C' . $r, $bf);
                $sheet->setCellValue('D' . $r, $dr);
                $sheet->setCellValue('E' . $r, $cr);
                $sheet->setCellValue('F' . $r, $cf);
                $r++;
            }
        }

        $filename = "template_ข้อมูลการเงิน_งวด_{$targetPeriod}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 6. Send HFA Trial Balance via API (POST /hfa_api/open-api/import/trial_balance)
     */
    public function sendTrialBalanceApi(Request $request)
    {
        $budgetYear = intval($request->input('fiscal_year', HosFinController::getCurrentBudgetYear()));
        $month = intval($request->input('month', 8));

        $periodYear = ($month >= 10) ? ($budgetYear - 1) : $budgetYear;
        $periodMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        $targetPeriod = "{$periodYear}-{$periodMonth}";

        $rows = DB::table('hosfin_trial_balance')
            ->where('acc_period', $targetPeriod)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบข้อมูลงบทดลองงวด {$targetPeriod} กรุณานำเข้างบทดลองก่อนส่ง API"
            ], 400);
        }

        // 1. Get Token from FDH
        $token = $this->getFdhAccessToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถขอ FDH Access Token ได้ กรุณาตรวจสอบการตั้งค่า FDH User / Pass ในโปรไฟล์'
            ], 400);
        }

        // 2. Generate Excel in temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'hfa_tb_') . '.xlsx';
        $templatePath = base_path('docs/template_ข้อมูลการเงิน.xlsx');
        if (file_exists($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $tbMap = [];
            foreach ($rows as $r) {
                $tbMap[trim($r->account_code)] = $r;
            }

            for ($r = 2; $r <= $highestRow; $r++) {
                $code = trim($sheet->getCell('A' . $r)->getValue());
                if (isset($tbMap[$code])) {
                    $item = $tbMap[$code];
                    $sheet->setCellValue('C' . $r, floatval($item->debit_bf) - floatval($item->credit_bf));
                    $sheet->setCellValue('D' . $r, floatval($item->debit_month));
                    $sheet->setCellValue('E' . $r, floatval($item->credit_month));
                    $sheet->setCellValue('F' . $r, floatval($item->debit_net) - floatval($item->credit_net));
                } else {
                    $sheet->setCellValue('C' . $r, 0.00);
                    $sheet->setCellValue('D' . $r, 0.00);
                    $sheet->setCellValue('E' . $r, 0.00);
                    $sheet->setCellValue('F' . $r, 0.00);
                }
            }
        } else {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('TrialBalance');
            $sheet->setCellValue('A1', 'รหัสบัญชี');
            $sheet->setCellValue('B1', 'ชื่อบัญชี');
            $sheet->setCellValue('C1', 'ยอดยกมา');
            $sheet->setCellValue('D1', 'เดบิต');
            $sheet->setCellValue('E1', 'เครดิต');
            $sheet->setCellValue('F1', 'ยอดยกไป');

            $r = 2;
            foreach ($rows as $item) {
                $sheet->setCellValueExplicit('A' . $r, $item->account_code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $r, $item->account_name);
                $sheet->setCellValue('C' . $r, floatval($item->debit_bf) - floatval($item->credit_bf));
                $sheet->setCellValue('D' . $r, floatval($item->debit_month));
                $sheet->setCellValue('E' . $r, floatval($item->credit_month));
                $sheet->setCellValue('F' . $r, floatval($item->debit_net) - floatval($item->credit_net));
                $r++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // 3. Send API to HFA
        $hfaUrl = config('services.hfa.url', 'https://hfa.one.th') . '/hfa_api/open-api/import/trial_balance';
        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 90])
                ->withToken($token)
                ->attach('file', fopen($tempPath, 'r'), "template_ข้อมูลการเงิน.xlsx")
                ->post($hfaUrl, [
                    'fiscal_year' => strval($budgetYear),
                    'month'       => strval($month)
                ]);

            @unlink($tempPath);

            $body = $response->json();
            if ($response->successful() && isset($body['status']) && $body['status'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => $body['message'] ?? 'ส่งข้อมูลงบทดลองเข้า HFA สำเร็จ',
                    'data'    => $body
                ]);
            } else {
                $errorMsg = $body['message_th'] ?? ($body['message'] ?? ($body['error'] ?? 'งบไม่ดุลหรือไม่สามารถบันทึกข้อมูลงบทดลองได้'));
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'raw'     => $body
                ], $response->status() ?: 400);
            }

        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::error("HFA Send Trial Balance API Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ HFA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Get FDH Access Token from MOPH Account Center
     */
    private function getFdhAccessToken(): ?string
    {
        $userObj = Auth::user();
        $user = $userObj->fdh_user ?? null;
        $password = $userObj->fdh_pass ?? null;
        $secretKey = $userObj->fdh_secretKey ?? null;

        // Fallback: If current user has no credentials, search for a valid user in database
        if (!$user || !$password || !$secretKey) {
            $fallbackUser = DB::table('users')
                ->whereNotNull('fdh_user')->where('fdh_user', '!=', '')
                ->whereNotNull('fdh_pass')->where('fdh_pass', '!=', '')
                ->whereNotNull('fdh_secretKey')->where('fdh_secretKey', '!=', '')
                ->first();

            if ($fallbackUser) {
                $user = $fallbackUser->fdh_user;
                $password = $fallbackUser->fdh_pass;
                $secretKey = $fallbackUser->fdh_secretKey;
            }
        }

        if (!$user || !$password || !$secretKey) {
            return null;
        }

        $settings = DB::table('main_setting')->pluck('value', 'name')->toArray();
        $userParts = explode('.', $user);
        $hcode = (count($userParts) > 1 && is_numeric(end($userParts)))
            ? end($userParts)
            : ($settings['hospital_code'] ?? ($settings['hcode'] ?? '10989'));

        $hash = strtoupper(hash_hmac('sha256', $password, $secretKey));
        $apiUrl = 'https://fdh.moph.go.th/token?Action=get_moph_access_token';

        try {
            $response = Http::withOptions(['verify' => false, 'timeout' => 15])
                ->withHeaders([
                    "Accept" => "application/json",
                    "Content-Type" => "application/json"
                ])->post($apiUrl, [
                    'user'          => $user,
                    'password_hash' => $hash,
                    'hospital_code' => $hcode
                ]);

            if ($response->successful()) {
                $token = trim($response->body());
                if (!empty($token) && !str_starts_with($token, '<!DOCTYPE') && !str_contains($token, 'Invalid') && !str_starts_with($token, '{')) {
                    return $token;
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to fetch FDH Token: " . $e->getMessage());
        }

        return null;
    }
}
