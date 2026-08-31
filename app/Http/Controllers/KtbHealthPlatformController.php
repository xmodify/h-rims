<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KtbHealthPlatformController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 1. การฝากครรภ์ (ANC)
     * สเปก KTB: 
     * - B17: 30014 (ทดสอบตั้งครรภ์)
     * - P02: 30011 (ดูแลการฝากครรภ์)
     * - P01: 30012 (LAB ครบรายการ)
     * - B55: 30013, 36003 (LAB VDRL, HIV)
     * - P69: 30010 (อัลตร้าซาวด์)
     * - D04: 30008, 30009 (ตรวจฟัน/ขัดฟัน)
     * - P05: 31001, 31002, 31003 (Hb typing หญิงตั้งครรภ์)
     * - P06: 31004 (Alpha-thalassemia 1 PCR)
     * - P07: 31005 (Beta-thalassemia)
     * - P57: 31001_1 (Hb typing คู่สมรส)
     * - P08/B56/B57: 32001, 32002, 32003 (วินิจฉัยทารกในครรภ์)
     * - B25/B18: 33001, 33002 (ปั่นซีรั่ม / Quadruple test)
     * - T01/B19: 34001, 34002 (โครโมโซมทารก)
     * - P03: 35001 (CBC+MCV / DCIP)
     * - B58/B59: 36001, 36002 (ตรวจ HIV, Syphilis ซ้ำ)
     * - B20: AB001, AB002, AB003 (การยุติการตั้งครรภ์)
     */
    public function anc(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'anc',
            'activity_code' => 'ANC',
            'page_title' => 'การฝากครรภ์ (ANC)',
            'gender_filter' => '2', // เฉพาะเพศหญิง
            'adp_codes' => [
                '30014', '30011', '30012', '30013', '36003', '30010', '30008', '30009',
                'AB001', 'AB002', 'AB003'
            ]
        ]);
    }

    /**
     * 2. การตรวจหลังคลอด (Postnatal)
     * สเปก KTB:
     * - P14/P98: 30015 (ค่าบริการตรวจหลังคลอด PNC)
     * - B60: 30016 (ยาเสริมธาตุเหล็กหลังคลอด Triferdine)
     */
    public function postnatal(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'postnatal',
            'activity_code' => 'POSTNATAL',
            'page_title' => 'การตรวจหลังคลอด',
            'adp_codes' => ['30015', '30016']
        ]);
    }

    /**
     * 3. การตรวจคัดกรองวินิจฉัยให้แว่นตาสำหรับเด็ก (Glasses)
     * สเปก KTB:
     * - G01: 2206, 2207 (แว่นตาเด็ก)
     */
    public function glasses(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'glasses',
            'activity_code' => 'G01',
            'page_title' => 'การตรวจคัดกรองวินิจฉัยให้แว่นตาสำหรับเด็ก',
            'adp_codes' => ['2206', '2207']
        ]);
    }

    /**
     * 4. บริการคัดกรองรอยโรคเสี่ยงมะเร็งและมะเร็งช่องปาก (Oral Cancer)
     * สเปก KTB:
     * - C28: 90004 (ค่าบริการตัดและตรวจชิ้นเนื้อ biopsy และพยาธิวิทยา)
     */
    public function oral_cancer(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'oral_cancer',
            'activity_code' => 'C28',
            'page_title' => 'บริการคัดกรองรอยโรคเสี่ยงมะเร็งและมะเร็งช่องปาก',
            'adp_codes' => ['90004']
        ]);
    }

    /**
     * 5. การตรวจคัดกรองมะเร็งสตรี (Cervical Cancer)
     * สเปก KTB:
     * - P53: 1B004P, 1B004N (Pap Smear)
     * - C29: 1B004_0P, 1B004_0N (VIA)
     * - P51: 1B0046_0, 1B0046_01, 1B0046_1, 1B0046_11, 1B0046_2, 1B0046_21 (HPV DNA Test)
     * - B43: 1B005 (Colposcope / Biopsy / LEEP)
     * - L43: 0320277_0, 0320277_1 (Liquid based cytology LBC)
     * - B92: 1B0046_3, 1B0046_4 (HPV DNA ต่างชาติ / ผู้สูงอายุ)
     */
    public function cervical_cancer(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'cervical_cancer',
            'activity_code' => 'CERVICAL',
            'page_title' => 'การตรวจคัดกรองมะเร็งสตรี',
            'adp_codes' => [
                '1B004P', '1B004N', '1B004_0P', '1B004_0N',
                '1B0046_0', '1B0046_01', '1B0046_1', '1B0046_11', '1B0046_2', '1B0046_21',
                '1B005', '0320277_0', '0320277_1', '1B0046_3', '1B0046_4'
            ]
        ]);
    }

    /**
     * 6. บริการคัดกรองโรคมะเร็งลำไส้ใหญ่ (Fit Test)
     * สเปก KTB:
     * - B39: 90005 (ตรวจคัดกรองมะเร็งลำไส้ใหญ่และลำไส้ตรง Fit test)
     */
    public function fittest(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'fittest',
            'activity_code' => 'B39',
            'page_title' => 'บริการคัดกรองโรคมะเร็งลำไส้ใหญ่ (Fit test)',
            'adp_codes' => ['90005']
        ]);
    }

    /**
     * 7. บริการคัดกรองและประเมินปัจจัยเสี่ยงต่อสุขภาพกาย/สุขภาพจิต (SCR)
     * สเปก KTB:
     * - S01: 12001 (ประเมินความดัน BMI รอบเอว เครียด บุหรี่ สุรา 15-34 ปี)
     * - S02: 12002, 12002_0, 12002_1 (ประเมินเบาหวาน ความดัน CVD 35-59 ปี)
     * - S03: 12003 (เจาะเลือด FPG น้ำตาล)
     * - S04: 12004 (เจาะเลือด Total Cholesterol / HDL)
     */
    public function scr(Request $request)
    {
        return $this->handleCategory($request, [
            'route_key' => 'scr',
            'activity_code' => 'SCR',
            'page_title' => 'บริการคัดกรองและประเมินปัจจัยเสี่ยงต่อสุขภาพกาย/สุขภาพจิต (SCR)',
            'adp_codes' => ['12001', '12002', '12003', '12004']
        ]);
    }

    /**
     * Alias for S01
     */
    public function s01(Request $request)
    {
        return $this->scr($request);
    }

    /**
     * Generic Handler สำหรับทั้ง 7 หมวด KTB Health Platform
     */
    protected function handleCategory(Request $request, array $config)
    {
        ini_set('max_execution_time', 0);

        $route_key = $config['route_key'];
        $activity_code = $config['activity_code'];
        $page_title = $config['page_title'];
        $adp_codes = $config['adp_codes'];
        $gender_filter = $config['gender_filter'] ?? null;
        $genderClause = $gender_filter ? "AND pt.sex = '{$gender_filter}'" : "";

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
        $year_data = DB::table('budget_year')
            ->whereIn('LEAVE_YEAR_ID', [$budget_year, $budget_year - 4])
            ->pluck('DATE_BEGIN', 'LEAVE_YEAR_ID');
        $start_date_b = $year_data[$budget_year] ?? null;
        $end_date_b = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('ktb.ktb_category', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date',
                'page_title',
                'route_key',
                'activity_code'
            ));
        }

        session()->save();

        // 1. ดึงรายการ icode ที่ตรงกับรหัส ADP ของหมวดนี้
        $icodes = DB::connection('hosxp')->table('nondrugitems')
            ->whereIn('nhso_adp_code', $adp_codes)
            ->pluck('icode')
            ->toArray();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!empty($icodes)) {
            $icodeList = "'" . implode("','", $icodes) . "'";

            // 2. Chart Monthly Summary Data
            if (!$request->input('skip_chart')) {
                $chartCacheKey = 'chart_ktb_' . $route_key . '_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
                $chartData = Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b, $icodeList, $genderClause) {
                    $sum_month_sql = "
                        SELECT op.vn, op.vstdate, SUM(op.sum_price) AS claim_price
                        FROM opitemrece op
                        INNER JOIN ovst o ON o.vn = op.vn
                        INNER JOIN patient pt ON pt.hn = o.hn
                        WHERE op.vstdate BETWEEN ? AND ?
                          AND op.paidst = '02'
                          AND op.icode IN ($icodeList)
                          AND (o.an = '' OR o.an IS NULL)
                          $genderClause
                        GROUP BY op.vn, op.vstdate, pt.cid
                    ";
                    $sum_month = DB::connection('hosxp')->select($sum_month_sql, [$start_date_b, $end_date_b]);

                    $grouped = [];
                    foreach ($sum_month as $row) {
                        $time = strtotime($row->vstdate);
                        $m = intval(date('n', $time));
                        $y = intval(date('Y', $time)) + 543;
                        $monthNames = [
                            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                            7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
                        ];
                        $monthStr = $monthNames[$m] . ' ' . substr($y, -2);
                        $key = date('Y-m', $time);
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0];
                        }
                        $grouped[$key]['claim_price'] += floatval($row->claim_price);
                    }
                    ksort($grouped);
                    return [
                        'month' => array_column($grouped, 'month'),
                        'claim_price' => array_column($grouped, 'claim_price')
                    ];
                });

                $month = $chartData['month'] ?? [];
                $claim_price = $chartData['claim_price'] ?? [];
            }

            // 3. รายการผู้รับบริการรายบุคคล
            $search_sql = "
                SELECT o.vn AS seq, o.vstdate, o.vsttime, o.oqueue, pt.cid, pt.hn,
                       CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                       p.name AS pttype, vp.hospmain, v.pdx,
                       (SELECT icd9 FROM doctor_operation WHERE vn = o.vn LIMIT 1) AS icd9,
                       COALESCE(os.cc, 'ตรวจประเมินคัดกรองสุขภาพ') AS cc,
                       os.bps, os.bpd, os.bw, os.height, os.bmi, os.waist,
                       COALESCE(v.income, (SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn), 0) AS income,
                       COALESCE(v.paid_money, (SELECT SUM(item_money) FROM opitemrece WHERE vn = o.vn AND paidst = '01'), 0) AS paid_money,
                       COALESCE(v.rcpt_money, (SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL), 0) AS rcpt_money,
                       SUM(op.sum_price) AS ppfs,
                       0.00 AS ems,
                       SUM(op.sum_price) AS claim_price,
                       'Y' AS request_funds,
                       0.00 AS receive_total,
                       GROUP_CONCAT(DISTINCT n.name) AS claim_list,
                       GROUP_CONCAT(DISTINCT n.nhso_adp_code) AS adp_codes,
                       pt.sex, v.age_y,
                       IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ''), 'Y', NULL) AS auth_code
                FROM opitemrece op
                INNER JOIN ovst o ON o.vn = op.vn
                INNER JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                LEFT JOIN pttype p ON p.pttype = vp.pttype
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN opdscreen os ON os.vn = o.vn
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                  AND op.paidst = '02'
                  AND op.icode IN ($icodeList)
                  AND (o.an = '' OR o.an IS NULL)
                  $genderClause
                GROUP BY o.vn, o.vstdate, o.vsttime, o.oqueue, pt.cid, pt.hn, ptname, p.name, vp.hospmain, v.pdx,
                         os.cc, os.bps, os.bpd, os.bw, os.height, os.bmi, os.waist, pt.sex, v.age_y, vp.auth_code,
                         v.income, v.paid_money, v.rcpt_money
                ORDER BY o.vstdate DESC, o.vsttime DESC
            ";

            $all_visits = DB::connection('hosxp')->select($search_sql, [$start_date, $end_date]);
        } else {
            $all_visits = [];
        }

        // Batch query nhso_endpoint เพื่อตรวจสอบสถานะปิดสิทธิ สปสช.
        $cids = array_filter(array_unique(array_column($all_visits, 'cid')));
        $endpointsMap = [];
        if (!empty($cids)) {
            try {
                $endpoints = DB::table('nhso_endpoint')
                    ->whereIn('cid', $cids)
                    ->where(function($query) {
                        $query->where('claimCode', 'LIKE', 'EP%')
                              ->orWhere('claim_status', 'success');
                    })
                    ->get()
                    ->groupBy('cid');
                foreach ($endpoints as $cid => $group) {
                    foreach ($group as $ep) {
                        $endpointsMap[$cid][$ep->vstdate] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore endpoint check error if table not accessible
            }
        }

        $search = [];
        foreach ($all_visits as $row) {
            $row->income = floatval($row->income);
            $row->paid_money = floatval($row->paid_money);
            $row->rcpt_money = floatval($row->rcpt_money);
            $row->ppfs = floatval($row->ppfs);
            $row->ems = floatval($row->ems);
            $row->claim_price = floatval($row->claim_price);

            // ตรวจสอบเงื่อนไขตามเกณฑ์เดียวกับ visit_details
            $errors = [];
            $warnings = [];

            if (empty($row->cid) || strlen(trim($row->cid)) !== 13) {
                $errors[] = "เลขประจำตัวประชาชน (CID) ไม่ถูกต้องหรือไม่มีข้อมูล";
            }
            if (empty($row->hn)) {
                $errors[] = "ไม่พบรหัสผู้ป่วย (HN)";
            }
            if (empty($row->pdx)) {
                $errors[] = "ไม่พบรหัสการวินิจฉัยโรคหลัก (PDX)";
            }
            if (empty($row->bps) || empty($row->bpd)) {
                $warnings[] = "ยังไม่ได้บันทึกความดันโลหิต (BPS / BPD)";
            }
            if (empty($row->bw) || floatval($row->bw) <= 0) {
                $warnings[] = "ยังไม่ได้บันทึกน้ำหนักตัว (BW)";
            }
            if (empty($row->height) || floatval($row->height) <= 0) {
                $warnings[] = "ยังไม่ได้บันทึกส่วนสูง (Height)";
            }

            $hasEp = isset($endpointsMap[$row->cid][$row->vstdate]);
            $row->endpoint = $hasEp ? 'Y' : null;
            $row->endpoint_valid = $hasEp;
            if (!$hasEp) {
                $warnings[] = "สิทธิ์การรักษายังไม่ได้ปิดสิทธิ์ในระบบ สปสช. (กรุณากดดึงข้อมูลหรือปิดสิทธิ์)";
            }

            $row->is_valid = empty($errors);
            $row->validation_errors = $errors;
            $row->validation_warnings = $warnings;

            $search[] = $row;
        }

        $table_html = view('ktb.ktb_category_table', compact(
            'budget_year',
            'start_date',
            'end_date',
            'search',
            'page_title',
            'route_key',
            'activity_code'
        ))->render();

        $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search);

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => !$request->input('skip_chart') ? [
                'months' => $month ?: [],
                'claim_price' => $claim_price ?: []
            ] : null
        ]);
    }

    /**
     * ดึงข้อมูลรายละเอียดการรับบริการและสัญญาณชีพสำหรับ Modal
     */
    public function visit_details(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN / SEQ'], 400);
        }

        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,
                   p.name AS pttype, p.hipdata_code, vp.hospmain,
                   COALESCE(os.cc, "ตรวจประเมินคัดกรองสุขภาพ") AS cc,
                   os.bps, os.bpd, os.pulse, os.rr, os.temperature, os.bw, os.height, os.bmi, os.waist,
                   (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   (SELECT i.name FROM ovstdiag od LEFT JOIN icd101 i ON i.code = od.icd10 WHERE od.vn = o.vn AND od.diagtype = "1" LIMIT 1) AS pdx_name,
                   v.income, v.uc_money, v.debt_id_list,
                   IFNULL(rc.rcpt_money, 0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status = "success" OR vp.claim_code LIKE "EP%"),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN (
                SELECT cid, vstdate,
                       MAX(CASE WHEN claimCode LIKE "EP%" OR claim_status = "success" THEN claimCode END) AS claimCode,
                       MAX(CASE WHEN claimCode LIKE "EP%" OR claim_status = "success" THEN "success" ELSE claim_status END) AS claim_status
                FROM hrims.nhso_endpoint
                GROUP BY cid, vstdate
            ) ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูล Visit']);
        }

        // รหัสโรครอง (Secondary Diagnoses)
        $secDiags = DB::connection('hosxp')->select('
            SELECT od.icd10, COALESCE(i.name, "") AS name
            FROM ovstdiag od
            LEFT JOIN icd101 i ON i.code = od.icd10
            WHERE od.vn = ? AND od.diagtype NOT IN ("1", "2")', [$vn]);
        $visit->sec_diags = $secDiags;
        $visit->sdx = implode(', ', array_map(fn($d) => $d->icd10 . ($d->name ? ' (' . $d->name . ')' : ''), $secDiags));

        // รหัสหัตถการ (ICD-9 / Procedures)
        $procedures = DB::connection('hosxp')->select('
            SELECT DISTINCT icd9, COALESCE(name, "") AS name FROM (
                SELECT od.icd10 AS icd9, i.name
                FROM ovstdiag od
                LEFT JOIN icd9cm1 i ON i.code = od.icd10
                WHERE od.vn = ? AND (od.diagtype = "2" OR od.icd10 REGEXP "^[0-9]")
                UNION
                SELECT d.icd9, i.name
                FROM doctor_operation d
                LEFT JOIN icd9cm1 i ON i.code = d.icd9
                WHERE d.vn = ? AND d.icd9 IS NOT NULL AND d.icd9 <> ""
            ) t', [$vn, $vn]);
        $visit->procedures = $procedures;
        $visit->icd9 = implode(', ', array_map(fn($p) => $p->icd9 . ($p->name ? ' (' . $p->name . ')' : ''), $procedures));

        // รายการเวชภัณฑ์ / ค่ารักษาพยาบาล / กิจกรรมคัดกรอง KTB (ดึงตรงจาก HOSxP 100% ไม่เชื่อมข้ามฐานข้อมูล)
        $items = DB::connection('hosxp')->select('
            SELECT op.item_no, op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                   COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmt_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmtid,
                   d.did, d.sks_drug_code
            FROM opitemrece op
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN (
                SELECT icode, MAX(ref_code) AS ref_code 
                FROM drugitems_ref_code 
                WHERE drugitems_ref_code_type_id = 3 
                GROUP BY icode
            ) d3 ON d3.icode = op.icode
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?
            ORDER BY op.item_no', [$vn]);

        // การตรวจสอบเงื่อนไขความถูกต้อง (Validation)
        $errors = [];
        $warnings = [];

        if (empty($visit->cid) || strlen(trim($visit->cid)) !== 13) {
            $errors[] = "เลขประจำตัวประชาชน (CID) ไม่ถูกต้องหรือไม่มีข้อมูล (จำเป็นสำหรับ 16 แฟ้ม KTB)";
        }
        if (empty($visit->hn)) {
            $errors[] = "ไม่พบรหัสผู้ป่วย (HN)";
        }
        if (empty($visit->pdx)) {
            $errors[] = "ไม่พบรหัสการวินิจฉัยโรคหลัก (PDX) กรุณาบันทึกผลการตรวจรักษา";
        }
        if (empty($visit->bps) || empty($visit->bpd)) {
            $warnings[] = "ยังไม่ได้บันทึกความดันโลหิต (BPS / BPD) ในระบบคัดกรอง";
        }
        if (empty($visit->bw) || floatval($visit->bw) <= 0) {
            $warnings[] = "ยังไม่ได้บันทึกน้ำหนักตัว (BW)";
        }
        if (empty($visit->height) || floatval($visit->height) <= 0) {
            $warnings[] = "ยังไม่ได้บันทึกส่วนสูง (Height)";
        }

        $endpointValid = ($visit->endpoint === 'Y');
        if (!$endpointValid) {
            $warnings[] = "สิทธิ์การรักษายังไม่ได้ปิดสิทธิ์ในระบบ สปสช. (กรุณากดดึงข้อมูลหรือปิดสิทธิ์)";
        }

        $validation = [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointValid,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];

        return response()->json([
            'success'    => true,
            'visit'      => $visit,
            'sec_diags'  => array_map(fn($d) => $d->icd10, $secDiags),
            'procedures' => array_map(fn($p) => $p->icd9, $procedures),
            'items'      => $items,
            'validation' => $validation,
        ]);
    }
}
