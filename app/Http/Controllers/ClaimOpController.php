<?php

namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClaimOpController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_claim_op !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'เรียกเก็บ OP'], 403);
                }
                return $next($request);
            }
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_incup(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ucs_incup', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ucs_incup_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $hospcodes = DB::table('lookup_hospcode')->where('hmain_ucs', 'Y')->pluck('hospcode')->toArray();
                $hospcode_in = !empty($hospcodes) ? "'" . implode("','", $hospcodes) . "'" : "''";

                $vns_data = DB::connection('hosxp')->select("
                    SELECT 
                        o.vn,
                        o.hn,
                        pt.cid,
                        o.vstdate,
                        LEFT(o.vsttime, 5) AS vsttime5,
                        YEAR(o.vstdate) AS yr,
                        MONTH(o.vstdate) AS mo,
                        SUM(op.sum_price) AS total_price
                    FROM ovst o
                    INNER JOIN visit_pttype vp ON vp.vn = o.vn
                    INNER JOIN pttype p ON p.pttype = vp.pttype AND p.hipdata_code IN ('UCS','WEL')
                    INNER JOIN patient pt ON pt.hn = o.hn
                    INNER JOIN opitemrece op ON op.vn = o.vn
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND (li.uc_cr = 'Y' OR li.ppfs = 'Y' OR li.herb32 = 'Y')
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an = '' OR o.an IS NULL)
                      AND vp.hospmain IN ($hospcode_in)
                    GROUP BY o.vn, o.hn, pt.cid, o.vstdate, o.vsttime
                ", [$start_date_b, $end_date_b]);

                $fdh_vns = DB::table('fdh_claim_status')->pluck('seq')->filter()->flip()->toArray();
                $eclaim_keys = DB::table('eclaim_status')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
                $rep_keys = DB::table('rep_ucs')->where('rep_type', 'OP')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();

                $stm_rows = DB::table('stm_ucs')
                    ->whereBetween('vstdate', [$start_date_b, $end_date_b])
                    ->selectRaw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5)) AS k, SUM(receive_total) AS rec_total")
                    ->groupBy(DB::raw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5))"))
                    ->pluck('rec_total', 'k')
                    ->toArray();

                $months_map = [
                    10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
                    1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.',
                    4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                    7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.'
                ];

                $monthly_agg = [];
                foreach ($vns_data as $row) {
                    $m = (int)$row->mo;
                    $y = (int)$row->yr;
                    $k_month = sprintf('%04d-%02d', $y, $m);

                    $hn_key = $row->hn . '_' . $row->vstdate . '_' . $row->vsttime5;
                    $cid_key = $row->cid . '_' . $row->vstdate . '_' . $row->vsttime5;

                    $is_sent = isset($fdh_vns[$row->vn]) 
                            || isset($eclaim_keys[$hn_key]) 
                            || isset($rep_keys[$hn_key]) 
                            || isset($stm_rows[$cid_key]);

                    $rec = $stm_rows[$cid_key] ?? 0;

                    if (!isset($monthly_agg[$k_month])) {
                        $short_year = substr((string)($y + 543), -2);
                        $month_name = ($months_map[$m] ?? $m) . ' ' . $short_year;
                        $monthly_agg[$k_month] = [
                            'month' => $month_name,
                            'claim_price' => 0,
                            'claim_sent_price' => 0,
                            'receive_total' => 0
                        ];
                    }

                    $monthly_agg[$k_month]['claim_price'] += (float)$row->total_price;
                    if ($is_sent) {
                        $monthly_agg[$k_month]['claim_sent_price'] += (float)$row->total_price;
                    }
                    $monthly_agg[$k_month]['receive_total'] += (float)$rec;
                }

                ksort($monthly_agg);
                return [
                    'month' => array_column($monthly_agg, 'month'),
                    'claim_price' => array_column($monthly_agg, 'claim_price'),
                    'claim_sent_price' => array_column($monthly_agg, 'claim_sent_price'),
                    'receive_total' => array_column($monthly_agg, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }


        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,claim_items.claim_list,
            v.income,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,
            COALESCE(claim_items.claim_price, 0) AS claim_price,
            claim_items.project,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn 
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_ucs
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND fdh.seq IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,
            o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            o.vn AS seq,v.income,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            claim_items.project,
            stm.receive_total,stm.repno,rep.error_code AS rep_error_code,rep.repno AS rep_repno,fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN li.uc_cr = "Y" THEN op.sum_price ELSE 0 END) AS uc_cr,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs,
                    SUM(CASE WHEN li.herb32 = "Y" THEN op.sum_price ELSE 0 END) AS herb,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")                
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            LEFT JOIN (
                SELECT * FROM (
                    SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY hn, vstdate, LEFT(vsttime, 5) 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5) 
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND (fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.uc_cr = "Y" OR li.ppfs = "Y" OR li.herb32 = "Y")',
                $allVns);
            $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
            $insUcsMap = [];
            if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
                $insUcsMap = DB::table('lookup_nhso_adp_code')
                    ->whereIn('nhso_adp_code', $adpCodes)
                    ->where('nhso_adp_type_id', 2)
                    ->pluck('ins_ucs', 'nhso_adp_code')
                    ->toArray();
            }
            foreach ($rawItems as $item) {
                $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validateUcs($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validateUcs($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.ucs_incup_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month,
                'claim_price' => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total' => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    // API: ดึงรายละเอียดการรับบริการสำหรับ Modal (Details + Validation)
    public function get_ucs_incup_visit_details(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN'], 400);
        }

        // ดึงข้อมูลหลักของ Visit
        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname,pt.fname," ",pt.lname) AS ptname,
                   p.name AS pttype, p.hipdata_code, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, v.uc_money, v.debt_id_list, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลการรับบริการ'], 404);
        }

        // รหัสโรครอง
        $secDiags = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->whereNotIn('diagtype', ['1', '2'])
            ->pluck('icd10')
            ->toArray();
        $visit->sdx = implode(',', $secDiags);

        // รหัสหัตถการ (ICD-9/Procedure)
        $procedures = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->where('diagtype', '2')
            ->pluck('icd10')
            ->toArray();
        $visit->icd9 = implode(',', $procedures);

        // รายการเวชภัณฑ์/ค่าใช้จ่ายที่เรียกเก็บ
        $items = DB::connection('hosxp')->select('
            SELECT op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.uc_cr, li.herb32,
                   COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                   COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmt_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmtid,
                   d.did, d.sks_drug_code
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

        // แนบ ins_ucs flag จาก lookup_nhso_adp_code เพื่อตรวจสอบว่าอยู่ในประกาศ UCS หรือเปล่า
        $adpCodes = collect($items)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
        $insUcsMap = [];
        if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
            $insRecords = DB::table('lookup_nhso_adp_code')
                ->whereIn('nhso_adp_code', $adpCodes)
                ->where('nhso_adp_type_id', 2)
                ->pluck('ins_ucs', 'nhso_adp_code');
            $insUcsMap = $insRecords->toArray();
        }
        foreach ($items as $item) {
            $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
        }

        // Validate
        $validator = new \App\Services\ClaimValidator();
        $validation = $validator->validateUcs($visit, $items);

        return response()->json([
            'visit'      => $visit,
            'sec_diags'  => $secDiags,
            'procedures' => $procedures,
            'items'      => $items,
            'validation' => $validation,
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ucs_inprovince', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ucs_inprovince_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $hospcodes = DB::table('lookup_hospcode')->where('in_province', 'Y')->where(function($q) {
                    $q->whereNull('hmain_ucs')->orWhere('hmain_ucs', '');
                })->pluck('hospcode')->toArray();
                $hospcode_in = !empty($hospcodes) ? "'" . implode("','", $hospcodes) . "'" : "''";

                $vns_data = DB::connection('hosxp')->select("
                    SELECT 
                        o.vn,
                        o.hn,
                        pt.cid,
                        o.vstdate,
                        LEFT(o.vsttime, 5) AS vsttime5,
                        YEAR(o.vstdate) AS yr,
                        MONTH(o.vstdate) AS mo,
                        SUM(op.sum_price) AS total_price
                    FROM ovst o
                    INNER JOIN visit_pttype vp ON vp.vn = o.vn
                    INNER JOIN pttype p ON p.pttype = vp.pttype AND p.hipdata_code IN ('UCS','WEL')
                    INNER JOIN patient pt ON pt.hn = o.hn
                    INNER JOIN opitemrece op ON op.vn = o.vn
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND (li.uc_cr = 'Y' OR li.ppfs = 'Y' OR li.herb32 = 'Y')
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an = '' OR o.an IS NULL)
                      AND vp.hospmain IN ($hospcode_in)
                    GROUP BY o.vn, o.hn, pt.cid, o.vstdate, o.vsttime
                ", [$start_date_b, $end_date_b]);

                $fdh_vns = DB::table('fdh_claim_status')->pluck('seq')->filter()->flip()->toArray();
                $eclaim_keys = DB::table('eclaim_status')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
                $rep_keys = DB::table('rep_ucs')->where('rep_type', 'OP')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();

                $stm_rows = DB::table('stm_ucs')
                    ->whereBetween('vstdate', [$start_date_b, $end_date_b])
                    ->selectRaw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5)) AS k, SUM(receive_total) AS rec_total")
                    ->groupBy(DB::raw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5))"))
                    ->pluck('rec_total', 'k')
                    ->toArray();

                $months_map = [
                    10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
                    1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.',
                    4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                    7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.'
                ];

                $monthly_agg = [];
                foreach ($vns_data as $row) {
                    $m = (int)$row->mo;
                    $y = (int)$row->yr;
                    $k_month = sprintf('%04d-%02d', $y, $m);

                    $hn_key = $row->hn . '_' . $row->vstdate . '_' . $row->vsttime5;
                    $cid_key = $row->cid . '_' . $row->vstdate . '_' . $row->vsttime5;

                    $is_sent = isset($fdh_vns[$row->vn]) 
                            || isset($eclaim_keys[$hn_key]) 
                            || isset($rep_keys[$hn_key]) 
                            || isset($stm_rows[$cid_key]);

                    $rec = $stm_rows[$cid_key] ?? 0;

                    if (!isset($monthly_agg[$k_month])) {
                        $short_year = substr((string)($y + 543), -2);
                        $month_name = ($months_map[$m] ?? $m) . ' ' . $short_year;
                        $monthly_agg[$k_month] = [
                            'month' => $month_name,
                            'claim_price' => 0,
                            'claim_sent_price' => 0,
                            'receive_total' => 0
                        ];
                    }

                    $monthly_agg[$k_month]['claim_price'] += (float)$row->total_price;
                    if ($is_sent) {
                        $monthly_agg[$k_month]['claim_sent_price'] += (float)$row->total_price;
                    }
                    $monthly_agg[$k_month]['receive_total'] += (float)$rec;
                }

                ksort($monthly_agg);
                return [
                    'month' => array_column($monthly_agg, 'month'),
                    'claim_price' => array_column($monthly_agg, 'claim_price'),
                    'claim_sent_price' => array_column($monthly_agg, 'claim_sent_price'),
                    'receive_total' => array_column($monthly_agg, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }


        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,claim_items.claim_list,
            v.income,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,
            COALESCE(claim_items.claim_price, 0) AS claim_price,
            claim_items.project,
            fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn           
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_ucs
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND fdh.seq IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn HAVING claim_price > 0 ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            vp.confirm_and_locked,vp.request_funds,
            o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            o.vn AS seq,v.income,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            claim_items.project,
            stm.receive_total,stm.repno,rep.error_code AS rep_error_code,rep.repno AS rep_repno,fdh.status_message_th AS fdh_status,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN li.uc_cr = "Y" THEN op.sum_price ELSE 0 END) AS uc_cr,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs,
                    SUM(CASE WHEN li.herb32 = "Y" THEN op.sum_price ELSE 0 END) AS herb,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")                
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            LEFT JOIN (
                SELECT * FROM (
                    SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY hn, vstdate, LEFT(vsttime, 5) 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND (fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.uc_cr = "Y" OR li.ppfs = "Y" OR li.herb32 = "Y")',
                $allVns);
            $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
            $insUcsMap = [];
            if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
                $insUcsMap = DB::table('lookup_nhso_adp_code')
                    ->whereIn('nhso_adp_code', $adpCodes)
                    ->where('nhso_adp_type_id', 2)
                    ->pluck('ins_ucs', 'nhso_adp_code')
                    ->toArray();
            }
            foreach ($rawItems as $item) {
                $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        if ($request->ajax()) {
            $table_html = view('claim_op.ucs_inprovince_table', compact('search', 'claim', 'budget_year', 'start_date', 'end_date'))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !empty($month) ? [
                    'month' => $month,
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.ucs_inprovince', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince_va(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $hcode = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');
        $lh_status = DB::table('lookup_hospcode')->where('hospcode', $hcode)->value('hmain_sss');
        $default_normal_price = ($lh_status === 'Y') ? 370 : 120;

        $sum = DB::connection('hosxp')->select('
            SELECT hospmain,COUNT(vn) AS visit,SUM(income) AS income,SUM(rcpt_money) AS rcpt_money,
            SUM(other_price) AS other_price,SUM(claim_price) AS claim_price,SUM(cfo_price) AS cfo_price,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN 1 ELSE 0 END) AS er_visit,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN claim_price ELSE 0 END) AS er_price,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN cfo_price ELSE 0 END) AS er_cfo_price,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN 1 ELSE 0 END) AS normal_visit,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN claim_price ELSE 0 END) AS normal_price,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN cfo_price ELSE 0 END) AS normal_cfo_price
			FROM (SELECT v.vn,CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
			    CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"
				WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" END AS pt_status,						
				o.vstdate,o.vsttime,p.`name` AS pttype,v.pdx,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(claim_items.other_price, 0) AS other_price,
				v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) AS claim_price,
				CASE 
				    WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN 
				        IF((v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)) > 700, 700, (v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)))
				    ELSE ' . $default_normal_price . '
				END AS cfo_price
                FROM ovst o
				LEFT JOIN er_regist er ON er.vn=o.vn
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN hrims.lookup_hospcode lh ON lh.hospcode = vp.hospmain
				LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
                LEFT JOIN pttype p ON p.pttype=vp.pttype
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn

				LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z480","Z098","Z549","Z479")
                LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
					WHERE op.vstdate BETWEEN ? AND ?  GROUP BY op.vn) claim_items ON claim_items.vn=o.vn            
                WHERE (o.an ="" OR o.an IS NULL) 
                    AND p.hipdata_code IN ("UCS","WEL") 
                    AND o.vstdate BETWEEN ? AND ? 
                    AND v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) <> 0
                    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
                    AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
                GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a	GROUP BY hospmain ORDER BY hospmain', [$start_date, $end_date, $start_date, $end_date]);

        $search = DB::connection('hosxp')->select('
            SELECT CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
            CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"			
			WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" 
            WHEN v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y" ) THEN "ส่งเสริมป้องกันโรคPP" 
			END AS pt_status,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
			p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            COALESCE(claim_items.total_income, 0) AS income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(claim_items.other_price, 0) AS other_price,COALESCE(claim_items.total_income, 0)-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) AS claim_price,
            CASE 
                WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN 
                    IF((COALESCE(claim_items.total_income, 0)-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)) > 700, 700, (COALESCE(claim_items.total_income, 0)-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)))
                ELSE ' . $default_normal_price . '
            END AS cfo_price,
            claim_items.other_list
            FROM ovst o
			LEFT JOIN er_regist er ON er.vn=o.vn
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN hrims.lookup_hospcode lh ON lh.hospcode = vp.hospmain
			LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn

			LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z480","Z098","Z549","Z479")
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.icode IS NOT NULL THEN sd.`name` END) AS other_list,
                    SUM(CASE WHEN li.icode IS NOT NULL THEN op.sum_price ELSE 0 END) AS other_price
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn            
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ? 
            AND COALESCE(claim_items.total_income, 0)-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) <> 0
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
            GROUP BY o.vn ORDER BY vp.hospmain,pt_status DESC,o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.ucs_inprovince_va', compact('start_date', 'end_date', 'sum', 'search'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outprovince(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ucs_outprovince', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ucs_outprovince_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $hospcodes = DB::table('lookup_hospcode')->where('in_province', 'Y')->pluck('hospcode')->toArray();
                $hospcode_in = !empty($hospcodes) ? "'" . implode("','", $hospcodes) . "'" : "''";

                $vns_data = DB::connection('hosxp')->select("
                    SELECT 
                        o.vn,
                        o.hn,
                        pt.cid,
                        o.vstdate,
                        LEFT(o.vsttime, 5) AS vsttime5,
                        YEAR(o.vstdate) AS yr,
                        MONTH(o.vstdate) AS mo,
                        COALESCE(v.income, 0) AS income,
                        IFNULL(rc.rcpt_money, 0) AS rcpt_money,
                        COALESCE(op_data.other_price, 0) AS other_price
                    FROM ovst o
                    INNER JOIN visit_pttype vp ON vp.vn = o.vn
                    INNER JOIN pttype p ON p.pttype = vp.pttype AND p.hipdata_code IN ('UCS','WEL')
                    INNER JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN (
                        SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                        FROM rcpt_print r
                        LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                        WHERE a.rcpno IS NULL
                        GROUP BY r.vn
                    ) rc ON rc.vn = o.vn
                    LEFT JOIN (
                        SELECT op.vn, 
                            SUM(CASE WHEN (li.ems = 'Y' OR li.kidney = 'Y') THEN op.sum_price ELSE 0 END) AS other_price
                        FROM opitemrece op
                        INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND (li.ems = 'Y' OR li.kidney = 'Y')
                        WHERE op.vstdate BETWEEN ? AND ?
                        GROUP BY op.vn
                    ) op_data ON op_data.vn = o.vn
                    WHERE o.vstdate BETWEEN ? AND ?
                      AND (o.an = '' OR o.an IS NULL)
                      AND vp.hospmain NOT IN ($hospcode_in)
                    GROUP BY o.vn, o.hn, pt.cid, o.vstdate, o.vsttime, v.income, rc.rcpt_money, op_data.other_price
                ", [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

                $fdh_vns = DB::table('fdh_claim_status')->pluck('seq')->filter()->flip()->toArray();
                $eclaim_keys = DB::table('eclaim_status')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
                $rep_keys = DB::table('rep_ucs')->where('rep_type', 'OP')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();

                $stm_rows = DB::table('stm_ucs')
                    ->whereBetween('vstdate', [$start_date_b, $end_date_b])
                    ->selectRaw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5)) AS k, SUM(receive_total) AS rec_total")
                    ->groupBy(DB::raw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5))"))
                    ->pluck('rec_total', 'k')
                    ->toArray();

                $months_map = [
                    10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
                    1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.',
                    4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                    7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.'
                ];

                $monthly_agg = [];
                foreach ($vns_data as $row) {
                    $m = (int)$row->mo;
                    $y = (int)$row->yr;
                    $k_month = sprintf('%04d-%02d', $y, $m);

                    $hn_key = $row->hn . '_' . $row->vstdate . '_' . $row->vsttime5;
                    $cid_key = $row->cid . '_' . $row->vstdate . '_' . $row->vsttime5;

                    $c_price = max(0, (float)$row->income - (float)$row->rcpt_money - (float)$row->other_price);

                    $is_sent = isset($fdh_vns[$row->vn]) 
                            || isset($eclaim_keys[$hn_key]) 
                            || isset($rep_keys[$hn_key]) 
                            || isset($stm_rows[$cid_key]);

                    $rec = $stm_rows[$cid_key] ?? 0;

                    if (!isset($monthly_agg[$k_month])) {
                        $short_year = substr((string)($y + 543), -2);
                        $month_name = ($months_map[$m] ?? $m) . ' ' . $short_year;
                        $monthly_agg[$k_month] = [
                            'month' => $month_name,
                            'claim_price' => 0,
                            'claim_sent_price' => 0,
                            'receive_total' => 0
                        ];
                    }

                    $monthly_agg[$k_month]['claim_price'] += $c_price;
                    if ($is_sent) {
                        $monthly_agg[$k_month]['claim_sent_price'] += $c_price;
                    }
                    $monthly_agg[$k_month]['receive_total'] += (float)$rec;
                }

                ksort($monthly_agg);
                return [
                    'month' => array_column($monthly_agg, 'month'),
                    'claim_price' => array_column($monthly_agg, 'claim_price'),
                    'claim_sent_price' => array_column($monthly_agg, 'claim_sent_price'),
                    'receive_total' => array_column($monthly_agg, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,COALESCE(op_data.total_income, 0) AS income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(op_data.other_price, 0) AS other_price,
            COALESCE(op_data.total_income, 0) - IFNULL(rc.rcpt_money, 0) - COALESCE(op_data.other_price, 0) AS claim_price,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_income,
                    SUM(CASE WHEN (li.ems = "Y" OR li.kidney = "Y") THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_ucs
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND fdh.seq IS NULL 
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn HAVING claim_price > 0 ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,COALESCE(op_data.total_income, 0) AS income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(op_data.other_price, 0) AS other_price,
            COALESCE(op_data.total_income, 0) - IFNULL(rc.rcpt_money, 0) - COALESCE(op_data.other_price, 0) AS claim_price,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            stm.receive_total,stm.repno,
            rep.error_code AS rep_error_code,rep.repno AS rep_repno,
            fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_income,
                    SUM(CASE WHEN (li.ems = "Y" OR li.kidney = "Y") THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT * FROM (
                    SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY hn, vstdate, LEFT(vsttime, 5) 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5) 
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND (fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs (Outprovince) ──────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32, li.kidney, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')',
                $allVns);
            $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
            $insUcsMap = [];
            if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
                $insUcsMap = DB::table('lookup_nhso_adp_code')
                    ->whereIn('nhso_adp_code', $adpCodes)
                    ->where('nhso_adp_type_id', 2)
                    ->pluck('ins_ucs', 'nhso_adp_code')
                    ->toArray();
            }
            foreach ($rawItems as $item) {
                $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validateUcs($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validateUcs($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        if ($request->ajax()) {
            $table_html = view('claim_op.ucs_outprovince_table', compact('search', 'claim', 'budget_year', 'start_date', 'end_date'))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !empty($month) ? [
                    'month' => $month,
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.ucs_outprovince', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function get_ucs_outprovince_visit_details(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN'], 400);
        }

        // ดึงข้อมูลหลักของ Visit
        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname,pt.fname," ",pt.lname) AS ptname,
                   p.name AS pttype, p.hipdata_code, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, v.uc_money, v.debt_id_list, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลการรับบริการ'], 404);
        }

        // รหัสโรครอง
        $secDiags = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->whereNotIn('diagtype', ['1', '2'])
            ->pluck('icd10')
            ->toArray();
        $visit->sdx = implode(',', $secDiags);

        // รหัสหัตถการ (ICD-9/Procedure)
        $procedures = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->where('diagtype', '2')
            ->pluck('icd10')
            ->toArray();
        $visit->icd9 = implode(',', $procedures);

        // รายการเวชภัณฑ์/ค่าใช้จ่ายที่เรียกเก็บ
        $items = DB::connection('hosxp')->select('
            SELECT op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.uc_cr, li.herb32,
                   COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                   COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmt_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmtid,
                   d.did, d.sks_drug_code
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

        // แนบ ins_ucs flag จาก lookup_nhso_adp_code เพื่อตรวจสอบว่าอยู่ในประกาศ UCS หรือเปล่า
        $adpCodes = collect($items)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
        $insUcsMap = [];
        if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
            $insRecords = DB::table('lookup_nhso_adp_code')
                ->whereIn('nhso_adp_code', $adpCodes)
                ->where('nhso_adp_type_id', 2)
                ->pluck('ins_ucs', 'nhso_adp_code');
            $insUcsMap = $insRecords->toArray();
        }
        foreach ($items as $item) {
            $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
        }

        // Validate
        $validator = new \App\Services\ClaimValidator();
        $validation = $validator->validateUcs($visit, $items);

        return response()->json([
            'visit'      => $visit,
            'sec_diags'  => $secDiags,
            'procedures' => $procedures,
            'items'      => $items,
            'validation' => $validation,
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_kidney(Request $request)
    {
        ini_set('max_execution_time', 0);

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // ── Early return for initial non-AJAX page load (Pattern 2) ────────
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ucs_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '512M');

        $sum_month = null;
        $month = [];
        $claim_price = [];
        $receive_total = [];

        // ── Conditional chart query (Pattern 3) ────────────────────────────
        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
                SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
                FROM (SELECT o.vstdate,o.vn,COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN pttype p ON p.pttype=vp.pttype
                INNER JOIN (
                    SELECT op.vn, SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                    FROM opitemrece op
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    WHERE op.vstdate BETWEEN ? AND ?
                    GROUP BY op.vn
                    HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
                ) kidney_items ON kidney_items.vn = o.vn
                LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                    WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid
                    AND stm.datetimeadm = o.vstdate
                WHERE p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── AJAX JSON response (Pattern 2) ──────────────────────────────────
        if ($request->ajax()) {
            $table_html = view('claim_op.ucs_kidney_table', compact(
                'search', 'claim', 'budget_year', 'start_date', 'end_date'
            ))->render();

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'chart_data' => $sum_month ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total,
                ] : null,
            ]);
        }

        return view('claim_op.ucs_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
     public function stp_incup(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // ── Early Return for Initial Page Load (Pattern 2) ──────────────────
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.stp_incup', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $sum_month = null;
        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        // ── Conditional Chart Query (Pattern 3) ─────────────────────────────
        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
                SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,
                    SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,
                    SUM(IFNULL(receive_total,0)) AS receive_total
                FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,stm.receive_total,
                      CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL OR rep.vn IS NOT NULL THEN (v.income-IFNULL(rc.rcpt_money, 0)) ELSE 0 END AS claim_sent_price
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN pttype p ON p.pttype=vp.pttype           
                LEFT JOIN vn_stat v ON v.vn = o.vn 
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn
                LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
                LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                    AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
                LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
                LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
                LEFT JOIN ( 
                    SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total
                    FROM hrims.stm_ucs
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
                ) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL) 
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP" 
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                GROUP BY o.vn  ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $visits = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IF(rep.vn IS NOT NULL,"Y",IF((oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL),"Y","N")) AS is_sent,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            v.income - IFNULL(rc.rcpt_money, 0) AS claim_price,
            stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y, pt.cid,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN IFNULL(n.`name`,d.`name`) END) AS claim_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn            
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_column($visits, 'seq');
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.uc_cr = "Y" OR li.ppfs = "Y" OR li.herb32 = "Y")',
                $allVns);
            $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
            $insUcsMap = [];
            if (!empty($adpCodes) && Schema::hasTable('lookup_nhso_adp_code')) {
                $insUcsMap = DB::table('lookup_nhso_adp_code')
                    ->whereIn('nhso_adp_code', $adpCodes)
                    ->where('nhso_adp_type_id', 2)
                    ->pluck('ins_ucs', 'nhso_adp_code')
                    ->toArray();
            }
            foreach ($rawItems as $item) {
                $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($visits as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        // ── AJAX JSON Response (Pattern 2) ──────────────────────────────────
        if ($request->ajax()) {
            $table_html = view('claim_op.stp_incup_table', compact(
                'visits', 'budget_year', 'start_date', 'end_date'
            ))->render();

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'chart_data' => $sum_month ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.stp_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'visits'));
    }

    public function stp_outcup(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // ── Early Return for Initial Page Load (Pattern 2) ──────────────────
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.stp_outcup', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $sum_month = null;
        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        // ── Conditional Chart Query (Pattern 3) ─────────────────────────────
        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
                SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,
                    SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,
                    SUM(IFNULL(receive_total,0)) AS receive_total
                FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,stm.receive_total,
                      CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL OR rep.vn IS NOT NULL THEN (v.income-IFNULL(rc.rcpt_money, 0)) ELSE 0 END AS claim_sent_price
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN pttype p ON p.pttype=vp.pttype           
                LEFT JOIN vn_stat v ON v.vn = o.vn 
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn
                LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
                LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                    AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
                LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
                LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
                LEFT JOIN ( 
                    SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total
                    FROM hrims.stm_ucs
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
                ) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL) 
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP" 
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                GROUP BY o.vn  ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $visits = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IF(rep.vn IS NOT NULL,"Y",IF((oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL),"Y","N")) AS is_sent,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            v.income - IFNULL(rc.rcpt_money, 0) AS claim_price,
            stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,
            pt.sex, v.age_y, pt.cid,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN IFNULL(n.`name`,d.`name`) END) AS claim_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn            
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_column($visits, 'seq');
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.uc_cr = "Y" OR li.ppfs = "Y" OR li.herb32 = "Y")',
                $allVns);
            $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
            $insUcsMap = [];
            if (!empty($adpCodes) && Schema::hasTable('lookup_nhso_adp_code')) {
                $insUcsMap = DB::table('lookup_nhso_adp_code')
                    ->whereIn('nhso_adp_code', $adpCodes)
                    ->where('nhso_adp_type_id', 2)
                    ->pluck('ins_ucs', 'nhso_adp_code')
                    ->toArray();
            }
            foreach ($rawItems as $item) {
                $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($visits as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        // ── AJAX JSON Response (Pattern 2) ──────────────────────────────────
        if ($request->ajax()) {
            $table_html = view('claim_op.stp_outcup_table', compact(
                'visits', 'budget_year', 'start_date', 'end_date'
            ))->render();

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'chart_data' => $sum_month ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.stp_outcup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'visits'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที
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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ofc', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    CASE 
                        WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                        WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,
                    SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,
                    SUM(IFNULL(receive_total,0)) AS receive_total
                FROM (SELECT o.vn,o.vstdate,
                IFNULL(v.income - IFNULL((
                    SELECT SUM(r.total_amount) 
                    FROM rcpt_print r 
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE r.vn = o.vn AND a.rcpno IS NULL
                ), 0), 0) AS claim_price,
                IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total,
                CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR csop.hn IS NOT NULL OR ec.seq IS NOT NULL OR rep.vstdate IS NOT NULL 
                     THEN IFNULL(v.income - IFNULL((
                         SELECT SUM(r.total_amount) 
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = o.vn AND a.rcpno IS NULL
                     ), 0), 0) 
                     ELSE 0 
                END AS claim_sent_price
                FROM ovst o        
                LEFT JOIN patient pt ON pt.hn=o.hn				
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN pttype p ON p.pttype=vp.pttype 
                LEFT JOIN vn_stat v ON v.vn = o.vn 	
                LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
                LEFT JOIN hrims.eclaim_status ec ON ec.seq = o.vn
                LEFT JOIN (
                    SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                    FROM hrims.stm_ofc 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY hn, vstdate, LEFT(vsttime,5)
                ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
                LEFT JOIN (
                    SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount
                    FROM hrims.stm_ofc_csop 
                    WHERE sys <> "HD" AND vstdate BETWEEN ? AND ?
                    GROUP BY hn, vstdate, LEFT(vsttime,5)
                ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)       
                LEFT JOIN (
                    SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5
                    FROM hrims.rep_ofc 
                    WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                    GROUP BY hn, vstdate, LEFT(vsttime,5)
                ) rep ON rep.hn = pt.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL) 
                AND p.hipdata_code = "OFC" 
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN (' . $pttype_checkup . ') 
                AND v.income <>"0" 
                AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
                GROUP BY o.vn  ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            COALESCE(op_data.ems_price, 0) AS ems_price,
            0 AS debtor,
            ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN s.`name` END) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS ems_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount
                FROM hrims.stm_ofc_csop 
                WHERE sys <> "HD" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)       
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5
                FROM hrims.rep_ofc 
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) rep ON rep.hn = pt.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.seq = o.vn
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ') 
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND csop.hn IS NULL
            AND rep.hn IS NULL
            AND ec.seq IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            COALESCE(op_data.ems_price, 0) AS ems_price,
            0 AS debtor,
            IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total,
            stm_uc.receive_pp,IFNULL(stm.repno,csop.rid) AS repno,ec.status AS ec_status,
            rep_eclaim.error_code AS rep_error_code, rep_eclaim.repno AS rep_repno,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN s.`name` END) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS ems_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop 
                WHERE sys <> "HD" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)  
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT * FROM (
                    SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY hn, vstdate, LEFT(vsttime, 5) 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ofc
                    WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                ) t1 WHERE t1.rn = 1
            ) rep_eclaim ON rep_eclaim.hn = pt.hn AND rep_eclaim.vstdate = o.vstdate AND rep_eclaim.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN hrims.eclaim_status ec ON ec.seq = o.vn
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR csop.hn IS NOT NULL OR ec.seq IS NOT NULL OR rep_eclaim.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money) - floatval($row->ems_price);
            if ($row->debtor > 0) {
                $result = $validator->validateOfc($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money) - floatval($row->ems_price);
            $result = $validator->validateOfc($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.ofc_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => !$request->input('skip_chart') ? [
                'month' => $month,
                'claim_price' => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total' => $receive_total
            ] : null
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    // API: ดึงรายละเอียดการรับบริการสำหรับ Modal (Details + Validation) ของ OFC
    public function get_ofc_visit_details(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN'], 400);
        }

        // ดึงข้อมูลหลักของ Visit
        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname,pt.fname," ",pt.lname) AS ptname,
                   p.name AS pttype, vp.hospmain, os.cc,
                   COALESCE((SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1), v.pdx) AS pdx,
                   v.income, IFNULL(v.paid_money,0) AS paid_money, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
                   COALESCE(stm.receive_total, 0) + COALESCE(csop.amount, 0) AS receive_total,
                   COALESCE(stm_uc.receive_pp, 0) AS receive_pp,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN ovst_seq oq ON oq.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY post_date DESC, post_time DESC, id DESC SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total
                FROM hrims.stm_ofc 
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount
                FROM hrims.stm_ofc_csop 
                WHERE sys <> "HD"
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)  
            LEFT JOIN (
                SELECT cid, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลการรับบริการ'], 404);
        }

        // รหัสโรครอง
        $secDiags = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->whereNotIn('diagtype', ['1', '2'])
            ->pluck('icd10')
            ->toArray();
        $visit->sdx = implode(',', $secDiags);

        // รหัสหัตถการ (ICD-9/Procedure)
        $procedures = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->where('diagtype', '2')
            ->pluck('icd10')
            ->toArray();
        $visit->icd9 = implode(',', $procedures);

        $items = DB::connection('hosxp')->select('
            SELECT op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.ems, op.paidst AS paids, ps.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name, 
                   COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                   COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmt_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmtid,
                   d.did, d.sks_drug_code
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst ps ON ps.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

        // Validate
        $validator = new \App\Services\ClaimValidator();
        $aspects = ['f16_required', 'ppfs', 'adp_ofc', 'endpoint'];
        if ($request->is('*ofc*')) {
            $aspects[] = 'edc';
        }
        $validation = $validator->validate($visit, $items, $aspects);

        return response()->json([
            'visit'      => $visit,
            'sec_diags'  => $secDiags,
            'procedures' => $procedures,
            'items'      => $items,
            'validation' => $validation,
        ]);
    }

    /**
     * อัปเดตเลขอนุมัติ EDC ตรงจากหน้าเว็บ KTB เข้าตาราง edc_approve_list
     */
    public function update_edc_manual(Request $request)
    {
        $cid = trim((string)$request->input('cid', ''));
        $vstdate = trim((string)$request->input('vstdate', ''));
        $approveCode = trim((string)$request->input('approve_code', ''));
        $ptname = trim((string)$request->input('ptname', ''));
        $amount = floatval($request->input('amount', 0));
        $vn = trim((string)$request->input('vn', ''));

        if (empty($cid) || empty($vstdate) || empty($approveCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณากรอกข้อมูล CID, วันที่รับบริการ และเลข Approve Code ให้ครบถ้วน'
            ], 422);
        }

        try {
            // Find existing record(s) for this cid and vstdate
            $existing = DB::table('edc_approve_list')
                ->where('cid', $cid)
                ->where('vstdate', $vstdate)
                ->orderBy('id', 'asc')
                ->get();

            if ($existing->isNotEmpty()) {
                // Update the primary record with the new approve_code (ทับเลขเดิม)
                $primaryId = $existing->first()->id;
                DB::table('edc_approve_list')
                    ->where('id', $primaryId)
                    ->update([
                        'approve_code' => $approveCode,
                        'app_code' => $approveCode,
                        'post_date' => date('Y-m-d'),
                        'post_time' => date('H:i:s'),
                        'amount' => $amount ?: $existing->first()->amount,
                        'note' => 'อัปเดตตรงจากหน้าเว็บ KTB',
                        'updated_at' => now(),
                    ]);

                // ลบรายการซ้ำซ้อนเดิมของวันรับบริการนั้นออก เพื่อให้เหลือเฉพาะเลขใหม่ที่อัปเดต
                if ($existing->count() > 1) {
                    $otherIds = $existing->pluck('id')->filter(function($id) use ($primaryId) {
                        return $id != $primaryId;
                    })->toArray();
                    DB::table('edc_approve_list')->whereIn('id', $otherIds)->delete();
                }
            } else {
                DB::table('edc_approve_list')->insert([
                    'cid' => $cid,
                    'ptname' => $ptname,
                    'vstdate' => $vstdate,
                    'vsttime' => date('H:i:s'),
                    'post_date' => date('Y-m-d'),
                    'post_time' => date('H:i:s'),
                    'amount' => $amount,
                    'approve_code' => $approveCode,
                    'app_code' => $approveCode,
                    'edc_type' => 'MANUAL_KTB',
                    'trans_type' => 'Payment',
                    'note' => 'อัปเดตตรงจากหน้าเว็บ KTB',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => "อัปเดตเลขอนุมัติ EDC ({$approveCode}) เรียบร้อยแล้ว",
                'approve_code' => $approveCode
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
            ], 500);
        }
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc_kidney(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // ── Early return for initial non-AJAX page load (Pattern 2) ────────
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.ofc_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '512M');

        $sum_month = null;
        $month = [];
        $claim_price = [];
        $receive_total = [];

        // ── Conditional chart query (Pattern 3) ────────────────────────────
        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
                SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                    WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                    END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
                FROM (SELECT o.vstdate,o.vn, COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(csop.amount, 0) AS receive_total 
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
                LEFT JOIN pttype p ON p.pttype=vp.pttype           
                LEFT JOIN vn_stat v ON v.vn = o.vn           

                INNER JOIN (
                    SELECT op.vn, SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                    FROM opitemrece op
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    WHERE op.vstdate BETWEEN ? AND ?
                    GROUP BY op.vn
                    HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
                ) kidney_items ON kidney_items.vn = o.vn
                LEFT JOIN (
                    SELECT hn, vstdate, SUM(amount) AS amount,MAX(rid) AS rid
                    FROM hrims.stm_ofc_csop 
                    WHERE sys = "HD" AND vstdate BETWEEN ? AND ?
                    GROUP BY hn, vstdate
                ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate 
                WHERE p.hipdata_code = "OFC" 
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(csop.amount, 0) AS receive_total ,csop.rid AS repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys = "HD" AND vstdate BETWEEN ? AND ? GROUP BY hn, vstdate) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate 
            WHERE p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND csop.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(csop.amount, 0) AS receive_total ,csop.rid AS repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys = "HD" AND vstdate BETWEEN ? AND ? GROUP BY hn, vstdate) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate 
            WHERE p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND csop.hn IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── AJAX JSON response (Pattern 2) ──────────────────────────────────
        if ($request->ajax()) {
            $table_html = view('claim_op.ofc_kidney_table', compact(
                'search', 'claim', 'budget_year', 'start_date', 'end_date'
            ))->render();

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'chart_data' => $sum_month ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total,
                ] : null,
            ]);
        }

        return view('claim_op.ofc_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
            public function lgo(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.lgo', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
            IFNULL(v.income - IFNULL((
                SELECT SUM(r.total_amount) 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE r.vn = o.vn AND a.rcpno IS NULL
            ), 0), 0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL 
                 THEN IFNULL(v.income - IFNULL((
                     SELECT SUM(r.total_amount) 
                     FROM rcpt_print r 
                     LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                     WHERE r.vn = o.vn AND a.rcpno IS NULL
                 ), 0), 0) 
                 ELSE 0 
            END AS claim_sent_price,
            IFNULL(COALESCE(rep.net_compensate_nhso, stm.compensate_treatment),0)+IFNULL(COALESCE(rep.net_compensate_employer, stm_uc.receive_pp),0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer
                FROM hrims.rep_lgo
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment, GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_lgo
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment, GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.cid IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,
            COALESCE(rep.net_compensate_nhso, stm.compensate_treatment) AS receive_total,
            COALESCE(rep.net_compensate_employer, stm_uc.receive_pp) AS receive_pp,
            COALESCE(rep.repno, stm.repno) AS repno,
            COALESCE(rep.error_code, ec.check_detail) AS check_detail, ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer,
                       MAX(error_code) AS error_code,
                       MAX(repno) AS repno
                FROM hrims.rep_lgo
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment, GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            if ($row->debtor > 0) {
                $result = $validator->validateLGO($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            $result = $validator->validateLGO($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.lgo_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month'            => $month,
                'claim_price'      => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total'    => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
public function lgo_kidney(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.lgo_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vn, COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn                 
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            INNER JOIN (
                SELECT op.vn, SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn           
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
            WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $table_html = view('claim_op.lgo_kidney_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => !empty($month) ? [
                'month' => $month,
                'claim_price' => $claim_price,
                'receive_total' => $receive_total
            ] : null
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
            public function bkk(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.bkk', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
            IFNULL(v.income - IFNULL((
                SELECT SUM(r.total_amount) 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE r.vn = o.vn AND a.rcpno IS NULL
            ), 0), 0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL 
                 THEN IFNULL(v.income - IFNULL((
                     SELECT SUM(r.total_amount) 
                     FROM rcpt_print r 
                     LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                     WHERE r.vn = o.vn AND a.rcpno IS NULL
                 ), 0), 0) 
                 ELSE 0 
            END AS claim_sent_price,
            IFNULL(COALESCE(rep.net_compensate_nhso, stm.receive_total),0)+IFNULL(COALESCE(rep.net_compensate_employer, stm_uc.receive_pp),0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer
                FROM hrims.rep_bkk
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_bkk
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,
            COALESCE(rep.net_compensate_nhso, stm.receive_total) AS receive_total,
            COALESCE(rep.net_compensate_employer, stm_uc.receive_pp) AS receive_pp,
            COALESCE(rep.repno, stm.repno) AS repno,
            COALESCE(rep.error_code, ec.check_detail) AS check_detail, ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer,
                       MAX(error_code) AS error_code,
                       MAX(repno) AS repno
                FROM hrims.rep_bkk
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            if ($row->debtor > 0) {
                $result = $validator->validateBkk($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            $result = $validator->validateBkk($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.bkk_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month'            => $month,
                'claim_price'      => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total'    => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
public function bkk_kidney(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.bkk_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vn, COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn                 
            INNER JOIN (
                SELECT op.vn, SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn           
            LEFT JOIN (
                SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bkk_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code IN ("BKK","PTY") AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) + COALESCE(stm_main.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bkk_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            LEFT JOIN (SELECT cid,vstdate,sum(receive_total) AS receive_total FROM hrims.stm_bkk
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm_main ON stm_main.cid=pt.cid AND stm_main.vstdate = o.vstdate
            WHERE p.hipdata_code IN ("BKK","PTY") AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NULL AND stm_main.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) + COALESCE(stm_main.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bkk_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            LEFT JOIN (SELECT cid,vstdate,sum(receive_total) AS receive_total FROM hrims.stm_bkk
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm_main ON stm_main.cid=pt.cid AND stm_main.vstdate = o.vstdate
            WHERE p.hipdata_code IN ("BKK","PTY") AND o.vstdate BETWEEN ? AND ?
            AND (stm.cid IS NOT NULL OR stm_main.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.bkk_kidney_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim'
            ))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.bkk_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
            public function bmt(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.bmt', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
            IFNULL(v.income - IFNULL((
                SELECT SUM(r.total_amount) 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE r.vn = o.vn AND a.rcpno IS NULL
            ), 0), 0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL 
                 THEN IFNULL(v.income - IFNULL((
                     SELECT SUM(r.total_amount) 
                     FROM rcpt_print r 
                     LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                     WHERE r.vn = o.vn AND a.rcpno IS NULL
                 ), 0), 0) 
                 ELSE 0 
            END AS claim_sent_price,
            IFNULL(COALESCE(rep.net_compensate_nhso, stm.receive_total),0)+IFNULL(COALESCE(rep.net_compensate_employer, stm_uc.receive_pp),0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer
                FROM hrims.rep_bmt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_bmt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,
            COALESCE(rep.net_compensate_nhso, stm.receive_total) AS receive_total,
            COALESCE(rep.net_compensate_employer, stm_uc.receive_pp) AS receive_pp,
            COALESCE(rep.repno, stm.repno) AS repno,
            COALESCE(rep.error_code, ec.check_detail) AS check_detail, ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer,
                       MAX(error_code) AS error_code,
                       MAX(repno) AS repno
                FROM hrims.rep_bmt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            if ($row->debtor > 0) {
                $result = $validator->validateBmt($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            $result = $validator->validateBmt($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.bmt_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month'            => $month,
                'claim_price'      => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total'    => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
public function bmt_kidney(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.bmt_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vn, COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn                 
            INNER JOIN (
                SELECT op.vn, SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn           
            LEFT JOIN (
                SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bmt_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm
            ) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bmt_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bmt_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.bmt_kidney_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim'
            ))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.bmt_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }

    //----------------------------------------------------------------------------------------------------------------------------------------
            public function srt(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.srt', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
            IFNULL(v.income - IFNULL((
                SELECT SUM(r.total_amount) 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE r.vn = o.vn AND a.rcpno IS NULL
            ), 0), 0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL 
                 THEN IFNULL(v.income - IFNULL((
                     SELECT SUM(r.total_amount) 
                     FROM rcpt_print r 
                     LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                     WHERE r.vn = o.vn AND a.rcpno IS NULL
                 ), 0), 0) 
                 ELSE 0 
            END AS claim_sent_price,
            IFNULL(COALESCE(rep.net_compensate_nhso, stm.receive_total),0)+IFNULL(COALESCE(rep.net_compensate_employer, stm_uc.receive_pp),0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer
                FROM hrims.rep_srt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_srt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,
            COALESCE(rep.net_compensate_nhso, stm.receive_total) AS receive_total,
            COALESCE(rep.net_compensate_employer, stm_uc.receive_pp) AS receive_pp,
            COALESCE(rep.repno, stm.repno) AS repno,
            COALESCE(rep.error_code, ec.check_detail) AS check_detail, ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer,
                       MAX(error_code) AS error_code,
                       MAX(repno) AS repno
                FROM hrims.rep_srt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            if ($row->debtor > 0) {
                $result = $validator->validateSrt($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            $result = $validator->validateSrt($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.srt_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month'            => $month,
                'claim_price'      => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total'    => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function pvt(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.pvt', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
            IFNULL(v.income - IFNULL((
                SELECT SUM(r.total_amount) 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE r.vn = o.vn AND a.rcpno IS NULL
            ), 0), 0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL 
                 THEN IFNULL(v.income - IFNULL((
                     SELECT SUM(r.total_amount) 
                     FROM rcpt_print r 
                     LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                     WHERE r.vn = o.vn AND a.rcpno IS NULL
                 ), 0), 0) 
                 ELSE 0 
            END AS claim_sent_price,
            IFNULL(COALESCE(rep.net_compensate_nhso, stm.receive_total),0)+IFNULL(COALESCE(rep.net_compensate_employer, stm_uc.receive_pp),0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer
                FROM hrims.rep_pvt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5
                FROM hrims.rep_pvt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND ec.hn IS NULL
            AND rep.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,COALESCE(op_data.total_income, 0) AS income,
            IFNULL(v.paid_money, 0) AS paid_money,
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = o.vn AND a.rcpno IS NULL
            ) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            0 AS debtor,
            COALESCE(rep.net_compensate_nhso, stm.receive_total) AS receive_total,
            COALESCE(rep.net_compensate_employer, stm_uc.receive_pp) AS receive_pp,
            COALESCE(rep.repno, stm.repno) AS repno,
            COALESCE(rep.error_code, ec.check_detail) AS check_detail, ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            0 AS ems_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    SUM(op.sum_price) AS total_income,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime, 5) AS vsttime5,
                       SUM(net_compensate_nhso) AS net_compensate_nhso,
                       SUM(net_compensate_employer) AS net_compensate_employer,
                       MAX(error_code) AS error_code,
                       MAX(repno) AS repno
                FROM hrims.rep_pvt
                WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime, 5)
            ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate AND rep.vsttime5 = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid = pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL OR rep.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y" OR n.nhso_adp_type_id = 20 OR d.nhso_adp_type_id = 20)',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        $filtered_search = [];
        foreach ($search as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            if ($row->debtor > 0) {
                $result = $validator->validatePvt($row, $itemsByVn[$row->seq] ?? []);
                $row->is_valid           = $result['is_valid'];
                $row->endpoint_valid     = $result['endpoint_valid'];
                $row->validation_errors  = $result['errors'];
                $row->validation_warnings = $result['warnings'];
                $filtered_search[] = $row;
            }
        }
        $search = $filtered_search;
        foreach ($claim as $row) {
            $row->debtor = floatval($row->income) - floatval($row->rcpt_money);
            $result = $validator->validatePvt($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        $table_html = view('claim_op.pvt_table', compact(
            'budget_year', 'start_date', 'end_date', 'search', 'claim'
        ))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month'            => $month,
                'claim_price'      => $claim_price,
                'claim_sent_price' => $claim_sent_price,
                'receive_total'    => $receive_total
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
public function sss_ppfs(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.sss_ppfs', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,
                SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,
                SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn, COALESCE(ppfs.claim_price, 0) AS claim_price,stm.receive_total,
            CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL THEN COALESCE(ppfs.claim_price, 0) ELSE 0 END AS claim_sent_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            INNER JOIN (
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                WHERE op.vstdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.vn
            ) ppfs ON ppfs.vn = o.vn           
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) 
			AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            op_data.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.claim_price, 0) AS claim_price,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status, MAX(ec.check_detail) AS check_detail,
            pt.sex, v.age_y, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`, d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND oe.moph_finance_upload_status IS NULL
            AND fdh.seq IS NULL
            AND ec.hn IS NULL
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            op_data.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.claim_price, 0) AS ppfs,
            rep.net_compensate_nhso AS rep_nhso,
            rep.error_code AS rep_error,stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status, MAX(ec.check_detail) AS check_detail,
            pt.sex, v.age_y, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`, d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.rep_sss rep ON rep.seq_no=o.vn AND rep.rep_type="OP"
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND (oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.ppfs = "Y"
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND op.paidst = "02"',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validatePpfsOnly($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validatePpfsOnly($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        if ($request->ajax()) {
            $table_html = view('claim_op.sss_ppfs_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim'
            ))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.sss_ppfs', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    // API: ดึงรายละเอียดการรับบริการสำหรับ Modal (Details + Validation) ของ PPFS
    public function get_sss_ppfs_visit_details(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN'], 400);
        }

        // ดึงข้อมูลหลักของ Visit
        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname,pt.fname," ",pt.lname) AS ptname,
                   p.name AS pttype, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลการรับบริการ'], 404);
        }

        // รหัสโรครอง
        $secDiags = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->whereNotIn('diagtype', ['1', '2'])
            ->pluck('icd10')
            ->toArray();
        $visit->sdx = implode(',', $secDiags);

        // รหัสหัตถการ (ICD-9/Procedure)
        $procedures = DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->where('diagtype', '2')
            ->pluck('icd10')
            ->toArray();
        $visit->icd9 = implode(',', $procedures);

        // รายการเวชภัณฑ์/ค่าใช้จ่ายที่เรียกเก็บเฉพาะ PPFS
        $items = DB::connection('hosxp')->select('
            SELECT op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.uc_cr, li.herb32,
                   COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type_id,
                   COALESCE(n.nhso_adp_code, d.nhso_adp_code, li.nhso_adp_code) AS nhso_adp_code,
                   op.paidst AS paids, ps.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmt_code,
                   COALESCE(NULLIF(d.sks_drug_code,""), NULLIF(d3.ref_code,""), NULLIF(d.tmt_tp_code,""), NULLIF(d.tmt_gp_code,""), NULLIF(d.ttmt_code,""), NULLIF(d.did,"")) AS tmtid,
                   d.did, d.sks_drug_code
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst ps ON ps.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

        // Validate
        $validator = new \App\Services\ClaimValidator();
        $validation = $validator->validatePpfsOnly($visit, $items);

        return response()->json([
            'visit'      => $visit,
            'sec_diags'  => $secDiags,
            'procedures' => $procedures,
            'items'      => $items,
            'validation' => $validation,
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_fund(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.sss_fund', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,d.receive AS receive_total
            FROM ovst o            
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN (
			    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
			    FROM rcpt_print r
			    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
			    WHERE a.rcpno IS NULL
			    GROUP BY r.vn
			) rc ON rc.vn = o.vn
            LEFT JOIN hrims.debtor_1102050101_307 d ON d.vn=o.vn
            WHERE p.pttype IN (' . $pttype_sss_fund . ') 
                AND (o.an = "" OR o.an IS NULL)
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE p.pttype IN (' . $pttype_sss_fund . ') 
            AND (o.an = "" OR o.an IS NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.sss_fund_table', compact(
                'budget_year', 'start_date', 'end_date', 'claim'
            ))->render();

            $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim);

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.sss_fund', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }

    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_kidney(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.sss_kidney', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vn, COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total 
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype         
            LEFT JOIN vn_stat v ON v.vn = o.vn                

            INNER JOIN (
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney = "Y"
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn            
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? AND hreg = hcode GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ? GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? AND hreg = hcode GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? AND hreg = hcode GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.sss_kidney_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim'
            ))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.sss_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_hc(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.sss_hc', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vn,COALESCE(hc_items.claim_price, 0) AS claim_price,d.receive AS receive_total
            FROM ovst o
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn             

            INNER JOIN (
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.sss_hc = "Y"
                WHERE op.vstdate BETWEEN ? AND ?
                AND op.vn IS NOT NULL 
                GROUP BY op.vn
                HAVING MAX(CASE WHEN op.paidst = "02" THEN 1 ELSE 0 END) = 1
            ) hc_items ON hc_items.vn = o.vn		
			LEFT JOIN hrims.debtor_1102050101_309 d ON d.vn=o.vn
			WHERE p.hipdata_code = "SSS" AND (o.an = "" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            hc_items.claim_list,
            COALESCE(hc_items.claim_price, 0) AS claim_price,d.receive AS receive_total,d.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn             
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(sd.`name`, n.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.sss_hc = "Y"
                LEFT JOIN nondrugitems n ON op.icode = n.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND op.vn IS NOT NULL 
                GROUP BY op.vn
                HAVING MAX(CASE WHEN op.paidst = "02" THEN 1 ELSE 0 END) = 1
            ) hc_items ON hc_items.vn = o.vn
            LEFT JOIN hrims.debtor_1102050101_309 d ON d.vn=o.vn		
			WHERE p.hipdata_code = "SSS" AND (o.an = "" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.sss_hc_table', compact(
                'budget_year', 'start_date', 'end_date', 'claim'
            ))->render();

            $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim);

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.sss_hc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function rcpt(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.rcpt', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_data = DB::connection('hosxp')->select('
            SELECT month,
                SUM(CASE WHEN debtor > 0 THEN 1 ELSE 0 END) AS visit_arrear,
                SUM(debtor) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT o.vstdate, o.vn,
                    v.paid_money - IFNULL(rc.rcpt_money, 0) AS debtor,
                    IFNULL(rc.rcpt_money, 0) AS receive_total,
                    CASE WHEN MONTH(o.vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        END AS month
                FROM ovst o
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn

                WHERE (o.an IS NULL OR o.an = "")
                    AND o.vstdate BETWEEN ? AND ?
                    AND v.paid_money > 0
                GROUP BY o.vn
            ) AS a
            GROUP BY month
            ORDER BY MIN(vstdate)', [$start_date_b, $end_date_b]);

            $month = array_column($sum_data, 'month');
            $claim_price = array_column($sum_data, 'claim_price');
            $receive_total = array_column($sum_data, 'receive_total');
        }

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, o.vstdate, o.vsttime, o.oqueue,o.vn, o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,os.cc,p.hipdata_code,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income, v.paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,v.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,rc.rcpno,
                p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"รอยืนยันลูกหนี้" AS status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN patient_arrear p2 ON p2.vn = o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn           
            LEFT JOIN vn_stat v ON v.vn = o.vn

            WHERE (o.an IS NULL OR o.an = "")
            AND v.paid_money > 0
            AND v.paid_money - IFNULL(rc.rcpt_money,0) > 0
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate, o.oqueue ', [$start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, o.vstdate, o.vsttime, o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
                os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,v.paid_money,
                v.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,r1.total_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn

            LEFT JOIN patient_arrear p2 ON p2.vn=o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.vn AND r1.`status` ="OK" AND r1.department="OPD" 
            WHERE (o.an IS NULL OR o.an ="") AND o.vstdate BETWEEN ? AND ? 
            AND v.paid_money > 0 AND v.paid_money - IFNULL(rc.rcpt_money,0) = 0
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.rcpt_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim'
            ))->render();

            $patient_items = array_merge(
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $search),
                array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim)
            );

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.rcpt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function act(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.act', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT month, COUNT(vn) AS visit, SUM(IFNULL(claim_price,0)) AS claim_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (
                SELECT o.vstdate, o.vn, v.income-IFNULL(rc.rcpt_money, 0) AS claim_price, d.receive AS receive_total,
                    CASE WHEN MONTH(o.vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        WHEN MONTH(o.vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(o.vstdate)+543, 2))
                        END AS month
                FROM ovst o            
                LEFT JOIN pttype p ON p.pttype=o.pttype
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn
                LEFT JOIN hrims.debtor_1102050102_602 d ON d.vn=o.vn
                WHERE p.pttype IN (' . $pttype_act . ') 
                    AND (o.an = "" OR o.an IS NULL)
                    AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn 
            ) AS a
            GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,
            d.receive AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN hrims.debtor_1102050102_602 d ON d.vn=o.vn
            WHERE p.pttype IN (' . $pttype_act . ') AND (o.an = "" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        if ($request->ajax()) {
            $table_html = view('claim_op.act_table', compact(
                'budget_year', 'start_date', 'end_date', 'claim'
            ))->render();

            $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim);

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.act', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }

    public function sss_main(Request $request)
    {
        ini_set('max_execution_time', 0);

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $pttype_sss_fund_raw = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: '';
        $pttype_sss_ae_raw = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: '';
        $exclude_pttypes = [];
        foreach (explode(',', $pttype_sss_fund_raw . ',' . $pttype_sss_ae_raw) as $p) {
            $trimmed = trim($p, " \t\n\r\0\x0B'");
            if ($trimmed !== '') {
                $exclude_pttypes[] = $trimmed;
            }
        }
        $exclude_pttypes_str = !empty($exclude_pttypes) ? "'" . implode("','", $exclude_pttypes) . "'" : "''";

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.sss_main', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,d.receive AS receive_total,
                  CASE WHEN rep.pid IS NOT NULL THEN (v.income-IFNULL(rc.rcpt_money, 0)) ELSE 0 END AS claim_sent_price
            FROM ovst o            
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN patient pt ON pt.hn=o.hn
			LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN (
			    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
			    FROM rcpt_print r
			    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
			    WHERE a.rcpno IS NULL
			    GROUP BY r.vn
			) rc ON rc.vn = o.vn
            LEFT JOIN hrims.debtor_1102050101_301 d ON d.vn=o.vn
            LEFT JOIN (
                SELECT pid, dttran_date, LEFT(dttran_time, 5) AS dttran_time_short
                FROM hrims.rep_sss_ssop
                GROUP BY pid, dttran_date, LEFT(dttran_time, 5)
            ) rep ON rep.pid = pt.cid
                AND rep.dttran_date = o.vstdate
                AND LEFT(o.vsttime, 5) = rep.dttran_time_short
            WHERE p.hipdata_code = "SSS"
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
                AND p.pttype NOT IN (' . $exclude_pttypes_str . ')
                AND (o.an = "" OR o.an IS NULL)
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vn,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain, vp.pttype AS sss_pttype,
            pt.cid, vp.begin_date, vp.expire_date,
            os.cc,
            doc.licenseno AS doctor_license, doc.name AS doctor_name,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            COALESCE((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND pttype = vp.pttype), v.income) AS income, v.uc_money, 
            IFNULL((SELECT SUM(r.total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND r.pttype = vp.pttype AND a.rcpno IS NULL), 0) AS rcpt_money, 
            d.receive AS receive_total,
            v.debt_id_list, osb.invno AS sss_invno, osb.billno AS sss_billno,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN hrims.debtor_1102050101_301 d ON d.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE p.hipdata_code = "SSS"
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND p.pttype NOT IN (' . $exclude_pttypes_str . ')
            AND (o.an = "" OR o.an IS NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        $ncd_json_path = storage_path('app/icd10_sss_chronic.json');
        if (!file_exists($ncd_json_path)) {
            $default_ncd = base_path('docs/lookup/icd10_sss_chronic.json');
            if (file_exists($default_ncd)) {
                @copy($default_ncd, $ncd_json_path);
            }
        }
        $ncd_data = [];
        if (file_exists($ncd_json_path)) {
            $ncd_data = json_decode(file_get_contents($ncd_json_path), true);
        }
        $prefixes = $ncd_data['prefixes'] ?? [];
        $exclusions = $ncd_data['exclusions'] ?? [];

        $tmt_json_path = storage_path('app/tmt_sss_chronic.json');
        if (!file_exists($tmt_json_path)) {
            $default_tmt = base_path('docs/lookup/tmt_sss_chronic.json');
            if (file_exists($default_tmt)) {
                @copy($default_tmt, $tmt_json_path);
            }
        }
        $tmt_diseases = [];
        if (file_exists($tmt_json_path)) {
            $tmt_data = json_decode(file_get_contents($tmt_json_path), true);
            $tmt_diseases = $tmt_data['diseases'] ?? [];
        }

        $vns = array_column($claim, 'vn');

        // SSS Debt mapping for multiple invoices using vn and SSS pttype
        $sss_debt_map = [];
        $sss_pttypes_by_vn = [];
        foreach ($claim as $row) {
            $sss_pttypes_by_vn[$row->vn] = $row->sss_pttype;
        }

        if (!empty($vns)) {
            $debt_records = DB::connection('hosxp')
                ->table('rcpt_debt as rd')
                ->whereIn('rd.vn', $vns)
                ->select('rd.vn', 'rd.debt_id', 'rd.pttype')
                ->get();
            foreach ($debt_records as $r) {
                $sss_pttype = $sss_pttypes_by_vn[$r->vn] ?? null;
                if ($sss_pttype !== null && $r->pttype === $sss_pttype) {
                    $sss_debt_map[$r->vn] = $r->debt_id;
                }
            }
        }

        $drugs_by_vn = [];
        $rep_errors = [];
        $stm_pays = [];

        if (!empty($vns)) {
            $placeholders = implode(',', array_fill(0, count($vns), '?'));
            $drugs = DB::connection('hosxp')->select("
                SELECT op.vn, op.icode, sd.name, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                       gt.gpu_code, gg.gp_code, COALESCE(di.sks_product_category_id, sd.sks_product_category_id) AS sks_product_category_id, di.capacity_name, di.capacity_qty,
                       op.drugusage, op.qty, op.income
                FROM opitemrece op
                INNER JOIN s_drugitems sd ON sd.icode = op.icode
                LEFT JOIN drugitems di ON di.icode = op.icode
                LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                    AND nd.date_approved = (
                        SELECT MAX(nd1.date_approved) 
                        FROM hrims.drugcat_chi nd1 
                        WHERE nd.hospdrugcode = nd1.hospdrugcode 
                        AND nd1.updateflag IN ('A','U','E')
                    )
                LEFT JOIN tmt_gpu_to_tpu gt ON gt.tpu_code = COALESCE(nd.tmtid, sd.sks_drug_code)
                LEFT JOIN tmt_gp_to_gpu gg ON gg.gpu_code = gt.gpu_code
                WHERE op.vn IN ($placeholders)
            ", $vns);
            foreach ($drugs as $d) {
                $drugs_by_vn[$d->vn][] = $d;
            }

            // Fetch latest REP error codes grouped by vn and station sorted chronologically (rep_date, rep_time, rep_no, rep_file, id)
            // Fetch REP records matching CID (pid) and Date (dttran_date)
            $cids = [];
            $dates = [];
            foreach ($claim as $row) {
                if (!empty($row->cid)) {
                    $cids[] = $row->cid;
                }
                if (!empty($row->vstdate)) {
                    $dates[] = $row->vstdate;
                }
            }
            $cids = array_unique($cids);
            $dates = array_unique($dates);

            $rep_records = [];
            if (!empty($cids) && !empty($dates)) {
                $rep_records = DB::table('rep_sss_ssop')
                    ->whereIn('pid', $cids)
                    ->whereIn('dttran_date', $dates)
                    ->orderByDesc('id') // Get latest records first
                    ->get();
            }

            // Map each visit to its corresponding rep records and status 'A' in PHP
            $rep_by_visit = [];
            $passed_a_by_visit = [];

            $rep_grouped = [];
            foreach ($rep_records as $rec) {
                $rec_time = substr($rec->dttran_time, 0, 5);
                $key = "{$rec->pid}_{$rec->dttran_date}_{$rec_time}";
                $rep_grouped[$key][] = $rec;
            }

            foreach ($claim as $row) {
                $row_time = substr($row->vsttime, 0, 5); // "09:20"
                $key = "{$row->cid}_{$row->vstdate}_{$row_time}";
                $matches = $rep_grouped[$key] ?? [];

                $latest_by_station = [];
                $has_passed_a = false;
                foreach ($matches as $rec) {
                    if ($rec->stat === 'A') {
                        $has_passed_a = true;
                    }
                    $station = $rec->station ?? '';
                    if (!isset($latest_by_station[$station])) {
                        $latest_by_station[$station] = $rec;
                    }
                }

                $rep_by_visit[$row->vn] = $latest_by_station;
                $passed_a_by_visit[$row->vn] = $has_passed_a;
            }

            $rep_errors = [];
            foreach ($claim as $row) {
                $vn = $row->vn;
                $latest_by_station = $rep_by_visit[$vn] ?? [];
                $has_passed_a = $passed_a_by_visit[$vn] ?? false;

                if (empty($latest_by_station)) {
                    continue;
                }

                if ($has_passed_a) {
                    $rep_errors[$vn] = ''; // Mark as has REP but no errors
                    continue;
                }

                $err_codes_accum = [];
                foreach ($latest_by_station as $rec) {
                    if (!empty($rec->error_codes)) {
                        $codes = array_filter(array_map('trim', explode(',', $rec->error_codes)));
                        foreach ($codes as $c) {
                            $err_codes_accum[] = strtoupper($c);
                        }
                    }
                }

                $err_codes_accum = array_unique($err_codes_accum);
                $filtered_err_codes = array_filter($err_codes_accum, function($c) {
                    return $c !== 'S01' && $c !== 'T02' && $c !== 'R01';
                });

                if (!empty($filtered_err_codes)) {
                    $rep_errors[$vn] = implode(',', $filtered_err_codes);
                } else {
                    $rep_errors[$vn] = ''; // Mark as has REP but no errors
                }
            }

            // Fetch STM paid amount matching by pid, dttran_date, and dttran_time (prefix)
            $stm_records = [];
            if (!empty($cids) && !empty($dates)) {
                $stm_records = DB::table('stm_sss_ssop')
                    ->whereIn('pid', $cids)
                    ->whereIn('dttran_date', $dates)
                    ->get();
            }

            $stm_pays = [];
            foreach ($stm_records as $rec) {
                $rec_time = substr($rec->dttran_time, 0, 5);
                $key = "{$rec->pid}_{$rec->dttran_date}_{$rec_time}";
                $stm_pays[$key] = ($stm_pays[$key] ?? 0.0) + (float)$rec->total;
            }
        }

        $validator = new \App\Services\ClaimValidator();
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $invo_str = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            if (isset($sss_debt_map[$row->vn])) {
                $row->sss_invno = (string)$sss_debt_map[$row->vn];
            } elseif (!empty($invo_str) && str_contains($invo_str, ',')) {
                $h_invoices = [];
                foreach (explode(',', $invo_str) as $part) {
                    $trimmed = trim($part);
                    if ($trimmed !== '') {
                        $h_invoices[] = $trimmed;
                    }
                }
                $row->sss_invno = !empty($h_invoices) ? $h_invoices[0] : '';
            } else {
                $row->sss_invno = $invo_str;
            }
            $diags = [];
            if (!empty($row->pdx)) {
                $diags[] = strtoupper(str_replace('.', '', trim($row->pdx)));
            }
            if (!empty($row->sdx)) {
                $sdx_list = explode(',', $row->sdx);
                foreach ($sdx_list as $s) {
                    $s_clean = strtoupper(str_replace('.', '', trim($s)));
                    if ($s_clean !== '') {
                        $diags[] = $s_clean;
                    }
                }
            }

            $row_diag_cats = [];
            $is_ncd = false;
            $is_exempted_ncd = false;
            foreach ($diags as $diag) {
                $is_excluded = false;
                foreach ($exclusions as $ex) {
                    if (str_starts_with($diag, $ex)) {
                        $is_excluded = true;
                        break;
                    }
                }
                if (!$is_excluded) {
                    foreach ($ncd_data['diseases'] ?? [] as $dis) {
                        $dis_id = $dis['id'];
                        foreach ($dis['prefixes'] as $pref) {
                            if (str_starts_with($diag, $pref)) {
                                $is_ncd = true;
                                $base_cat = substr($dis_id, 0, 2);
                                $row_diag_cats[$base_cat] = true;
                                if (in_array($dis_id, ['03', '03A', '03B', '04', '06', '19', '20'])) {
                                    $is_exempted_ncd = true;
                                }
                            }
                        }
                    }
                }
            }
            $row->is_ncd = $is_ncd;
            $row->is_exempted_ncd = $is_exempted_ncd;
 
            $visit_drugs = $drugs_by_vn[$row->vn] ?? [];
            $row_drug_cats = [];
            $has_chronic_drug = false;
            foreach ($visit_drugs as $drug) {
                $drug_matched = false;
                foreach ($tmt_diseases as $dis) {
                    $dis_id = $dis['id'];
                    $is_gp = !empty($drug->gp_code) && in_array($drug->gp_code, $dis['gp_codes'] ?? []);
                    $is_gpu = !empty($drug->gpu_code) && in_array($drug->gpu_code, $dis['gpu_codes'] ?? []);
                    $is_tpu = !empty($drug->tmtid) && in_array($drug->tmtid, $dis['tpu_codes'] ?? []);
                    if ($is_gp || $is_gpu || $is_tpu) {
                        $base_cat = substr($dis_id, 0, 2);
                        $row_drug_cats[$base_cat] = true;
                        $drug_matched = true;
                    }
                }
                if ($drug_matched) {
                    $has_chronic_drug = true;
                }
            }
            $row->has_chronic_drug = $has_chronic_drug;
 
            $intersect = array_intersect(array_keys($row_diag_cats), array_keys($row_drug_cats));
            $row->has_matching_category = !empty($intersect);

            if ($row->is_ncd && $row->has_matching_category) {
                $row->chronic_status = 'green';
            } elseif ($row->is_exempted_ncd) {
                $row->chronic_status = 'green';
            } elseif ($row->is_ncd || $row->has_chronic_drug) {
                $row->chronic_status = 'red';
            } else {
                $row->chronic_status = 'grey';
            }

            // Calculate general readiness claim_status based on: InvoiceNo, PDX, uc_money > 0, CID, Hmain, and Drug Audits
            $invoice_no = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $has_pdx = !empty($row->pdx);
            $has_claim_money = floatval($row->uc_money) > 0;
            $has_valid_cid = !empty($row->cid) && strlen(trim($row->cid)) === 13;
            
            // Check C07: Hospital Main in network
            $has_valid_hmain = false;
            if (!empty($row->hospmain)) {
                $has_valid_hmain = DB::table('lookup_hospcode')
                    ->where('hospcode', $row->hospmain)
                    ->where('hmain_sss', 'Y')
                    ->exists();
            }

            // Check C02: Visit date within coverage range
            $has_valid_dates = true;
            if (!empty($row->begin_date) && strtotime($row->vstdate) < strtotime($row->begin_date)) {
                $has_valid_dates = false;
            }
            if (!empty($row->expire_date) && strtotime($row->vstdate) > strtotime($row->expire_date)) {
                $has_valid_dates = false;
            }
            
            // Check drug errors
            $has_drug_error = false;
            foreach ($visit_drugs as $drug) {
                if (!str_starts_with($drug->icode, '1')) {
                    continue;
                }
                if (empty($drug->capacity_name) || empty($drug->capacity_qty) || floatval($drug->capacity_qty) <= 0 ||
                    empty($drug->sks_product_category_id) || intval($drug->sks_product_category_id) <= 0 ||
                    empty($drug->drugusage) || empty($drug->qty)) {
                    $has_drug_error = true;
                    break;
                }
            }
            
            // Set REP Error, Warning and STM paid
            $raw_rep = $rep_errors[$row->vn] ?? null;
            $row->rep_error = null;
            $row->rep_warning = null;
            if ($raw_rep) {
                $codes = array_filter(array_map('trim', explode(',', $raw_rep)));
                $err_codes = [];
                $warn_codes = [];
                foreach ($codes as $c) {
                    if (str_starts_with(strtoupper($c), 'W')) {
                        $warn_codes[] = $c;
                    } else {
                        $err_codes[] = $c;
                    }
                }
                $row->rep_error = !empty($err_codes) ? implode(', ', $err_codes) : null;
                $row->rep_warning = !empty($warn_codes) ? implode(', ', $warn_codes) : null;
            }
            $row_time = substr($row->vsttime, 0, 5);
            $stm_key = "{$row->cid}_{$row->vstdate}_{$row_time}";
            $row->stm_pay = $stm_pays[$stm_key] ?? null;

            // Check ICD-10 CHI validation (Validate ONLY primary diagnosis/PDX)
            $has_icd10_chi_error = false;
            if (!empty($row->pdx)) {
                $res = $validator->validateIcd10Chi($row->pdx, '1');
                if (!$res['is_valid']) {
                    $has_icd10_chi_error = true;
                }
            }

            // Check SvPID (S15) doctor license formatting check
            $has_valid_doc_license = false;
            if (!empty($row->doctor_license)) {
                $lic = trim($row->doctor_license);
                $has_valid_doc_license = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
            }

            // Check Pharmacist license presence (if drugs exist)
            $has_valid_pharmacist = true;
            if (!empty($visit_drugs)) {
                $lic = !empty($row->doctor_license) ? trim($row->doctor_license) : '';
                if (empty($lic) || $lic === '-') {
                    $has_valid_pharmacist = false;
                }
            }

            // Check TMT ID for modern medicines
            $has_tmt_error = false;
            foreach ($visit_drugs as $drug) {
                $item_prdcat = !empty($drug->sks_product_category_id) ? (string)$drug->sks_product_category_id : '';
                if (str_starts_with($drug->icode, '3')) {
                    if (isset($drug->income) && $drug->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat) || !in_array($item_prdcat, ['1', '2', '3', '4', '5'])) {
                    $item_prdcat = '1';
                }
                if ($item_prdcat === '1' && empty($drug->tmtid)) {
                    $has_tmt_error = true;
                    break;
                }
            }

            // Determine eye status color: red (errors), yellow (warnings/not closed), green (all good & closed)
            $is_valid = (!empty($invoice_no) && $invoice_no !== '0' && $invoice_no !== '0.00' && $has_pdx && $has_claim_money && $has_valid_cid && $has_valid_hmain && $has_valid_dates && !$has_icd10_chi_error && $has_valid_doc_license && $has_valid_pharmacist && !$has_tmt_error);
            if (!$is_valid) {
                $row->claim_status = 'red';
            } elseif ($row->endpoint !== 'Y') {
                $row->claim_status = 'yellow';
            } else {
                $row->claim_status = 'green';
            }

        }

        $search = [];
        $claim_sent = [];
        $warning = [];

        foreach ($claim as $row) {
            $has_rep = isset($rep_errors[$row->vn]);
            
            if ($row->rep_error) {
                $warning[] = $row;
            } elseif ($has_rep || $row->stm_pay !== null) {
                $claim_sent[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $claim_data = $claim; // Keep original
        $claim = $claim_sent; // Assign sent claim to $claim variable

        if ($request->ajax()) {
            $table_html = view('claim_op.sss_main_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim', 'warning'
            ))->render();

            $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $claim_data);

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'patient_items' => $patient_items,
                'chart_data' => !$request->input('skip_chart') ? [
                    'months' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.sss_main', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim', 'warning'));
    }

    public function sss_detail(Request $request)
    {
        $vn = $request->vn;
        if (empty($vn)) {
            return response()->json(['error' => 'Invalid VN'], 400);
        }

        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vstdate, o.vsttime, pt.hn, pt.sex, v.age_y, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, pt.cid,
                   p.name AS pttype_name, p.hipdata_code, os.cc, v.pdx, v.income, v.uc_money, IFNULL(rc.rcpt_money, 0) AS rcpt_money,
                   rc.rcpno_list, v.debt_id_list, osb.invno AS sss_invno, osb.billno AS sss_billno,
                   vp.begin_date, vp.expire_date, vp.hospmain, vp.hospsub, vp.pttypeno, v.paid_money, v.remain_money,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money, GROUP_CONCAT(r.rcpno) AS rcpno_list
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            WHERE o.vn = ?
        ', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'Visit not found'], 404);
        }

        $diagnoses = DB::connection('hosxp')->select('
            SELECT icd10, diagtype 
            FROM ovstdiag 
            WHERE vn = ?
        ', [$vn]);

        $drugs = DB::connection('hosxp')->select("
            SELECT op.icode, COALESCE(sd.name, ni.name) AS name, op.qty, op.sum_price, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                   gt.gpu_code, gg.gp_code, op.drugusage,
                   CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text,
                   COALESCE(di.sks_product_category_id, sd.sks_product_category_id) AS sks_product_category_id, di.capacity_name, di.capacity_qty,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name, ni.nhso_adp_code, op.income,
                   inc.income_csmbs_code
            FROM opitemrece op
            LEFT JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN drugusage du ON du.drugusage = op.drugusage
            LEFT JOIN nondrugitems ni ON ni.icode = op.icode
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            LEFT JOIN tmt_gpu_to_tpu gt ON gt.tpu_code = COALESCE(nd.tmtid, sd.sks_drug_code)
            LEFT JOIN tmt_gp_to_gpu gg ON gg.gpu_code = gt.gpu_code
            LEFT JOIN income inc ON inc.income = op.income
            WHERE op.vn = ?
        ", [$vn]);

        $tmt_json_path = storage_path('app/tmt_sss_chronic.json');
        if (!file_exists($tmt_json_path)) {
            $default_tmt = base_path('docs/lookup/tmt_sss_chronic.json');
            if (file_exists($default_tmt)) {
                @copy($default_tmt, $tmt_json_path);
            }
        }
        $tmt_diseases = [];
        if (file_exists($tmt_json_path)) {
            $tmt_data = json_decode(file_get_contents($tmt_json_path), true);
            $tmt_diseases = $tmt_data['diseases'] ?? [];
        }

        $ncd_json_path = storage_path('app/icd10_sss_chronic.json');
        if (!file_exists($ncd_json_path)) {
            $default_ncd = base_path('docs/lookup/icd10_sss_chronic.json');
            if (file_exists($default_ncd)) {
                @copy($default_ncd, $ncd_json_path);
            }
        }
        $ncd_data = [];
        if (file_exists($ncd_json_path)) {
            $ncd_data = json_decode(file_get_contents($ncd_json_path), true);
        }
        $exclusions = $ncd_data['exclusions'] ?? [];

        $is_ncd = false;
        $is_pdx_ncd = false;
        $is_exempted_ncd = false;
        $visit_diag_cats = [];

        foreach ($diagnoses as $d) {
            $is_ncd_item = false;
            if ($d->diagtype != '2') {
                $diag = strtoupper(str_replace('.', '', trim($d->icd10 ?? '')));
                if ($diag !== '') {
                    $is_excluded = false;
                    foreach ($exclusions as $ex) {
                        if (str_starts_with($diag, $ex)) {
                            $is_excluded = true;
                            break;
                        }
                    }
                    if (!$is_excluded) {
                        foreach ($ncd_data['diseases'] ?? [] as $dis) {
                            $dis_id = $dis['id'];
                            foreach ($dis['prefixes'] as $pref) {
                                if (str_starts_with($diag, $pref)) {
                                    $is_ncd_item = true;
                                    $is_ncd = true;
                                    $base_cat = substr($dis_id, 0, 2);
                                    $visit_diag_cats[$base_cat] = true;
                                    if ($d->diagtype == '1') {
                                        $is_pdx_ncd = true;
                                    }
                                    if (in_array($dis_id, ['03', '03A', '03B', '04', '06', '19', '20'])) {
                                        $is_exempted_ncd = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $d->is_chronic = $is_ncd_item;
        }
        $visit->is_ncd = $is_ncd;
        $visit->is_pdx_ncd = $is_pdx_ncd;
        $visit->is_exempted_ncd = $is_exempted_ncd;

        $visit_drug_cats = [];
        foreach ($drugs as $drug) {
            $drug_matched = false;
            foreach ($tmt_diseases as $dis) {
                $dis_id = $dis['id'];
                $is_gp = !empty($drug->gp_code) && in_array($drug->gp_code, $dis['gp_codes'] ?? []);
                $is_gpu = !empty($drug->gpu_code) && in_array($drug->gpu_code, $dis['gpu_codes'] ?? []);
                $is_tpu = !empty($drug->tmtid) && in_array($drug->tmtid, $dis['tpu_codes'] ?? []);
                if ($is_gp || $is_gpu || $is_tpu) {
                    $base_cat = substr($dis_id, 0, 2);
                    $visit_drug_cats[$base_cat] = true;
                    $drug_matched = true;
                }
            }
            $drug->is_chronic = $drug_matched;
        }

        $intersect = array_intersect(array_keys($visit_diag_cats), array_keys($visit_drug_cats));
        $visit->has_matching_category = !empty($intersect);

        $rep_feedbacks = [];
        $raw_records = DB::table('rep_sss_ssop')
            ->where('pid', $visit->cid)
            ->where('dttran_date', $visit->vstdate)
            ->where(DB::raw("LEFT(dttran_time, 5)"), substr($visit->vsttime, 0, 5))
            ->orderByRaw("COALESCE(rep_date, '1970-01-01') DESC")
            ->orderByRaw("COALESCE(rep_time, '00:00:00') DESC")
            ->orderByRaw("COALESCE(rep_no, '') DESC")
            ->orderByRaw("COALESCE(rep_file, '') DESC")
            ->orderByDesc('id')
            ->get();

        $rep_records = [];
        $seen_stations = [];
        foreach ($raw_records as $rec) {
            $station = $rec->station ?? '';
            if (!in_array($station, $seen_stations)) {
                $seen_stations[] = $station;
                $rep_records[] = $rec;
            }
        }

        $lookup = [];
        $json_path = base_path('docs/lookup/sss_error_codes.json');
        if (file_exists($json_path)) {
            $lookup = json_decode(file_get_contents($json_path), true) ?: [];
        }

        foreach ($rep_records as $rep_record) {
            if ($rep_record->stat === 'A') {
                continue;
            }
            if ($rep_record && !empty($rep_record->error_codes)) {
                $codes = array_filter(array_map('trim', explode(',', $rep_record->error_codes)));
                foreach ($codes as $c) {
                    $desc = $lookup[$c] ?? 'ไม่พบข้อมูลในคู่มือ';
                    $is_warn = str_starts_with(strtoupper($c), 'W');
                    $rep_feedbacks[] = [
                        'code' => $c,
                        'type' => $is_warn ? 'warning' : 'error',
                        'desc' => $desc
                    ];
                }
            }
        }

        // Pre-audit validation before export (Predict C-code rejections)
        $pre_audits = [];

        // 1. Audit C01: Check Citizen ID (CID)
        if (empty($visit->cid) || strlen(trim($visit->cid)) !== 13) {
            $pre_audits[] = [
                'code' => 'C01',
                'title' => 'ไม่มีสิทธิประกันสังคม / ข้อมูลสิทธิไม่สมบูรณ์',
                'desc' => 'ไม่พบเลขบัตรประชาชน (CID) หรือความยาวเลขบัตรไม่ครบ 13 หลัก',
                'status' => 'danger'
            ];
        }

        // 2. Audit C02: Check privilege validity dates
        if (!empty($visit->begin_date) && strtotime($visit->vstdate) < strtotime($visit->begin_date)) {
            $pre_audits[] = [
                'code' => 'C02',
                'title' => 'วันที่รักษา (dttran) ไม่มีสิทธิประกันสังคม',
                'desc' => 'วันที่มารับบริการ (' . DateThai($visit->vstdate) . ') ก่อนวันเริ่มต้นคุ้มครองของสิทธิ (' . DateThai($visit->begin_date) . ')',
                'status' => 'danger'
            ];
        }
        if (!empty($visit->expire_date) && strtotime($visit->vstdate) > strtotime($visit->expire_date)) {
            $pre_audits[] = [
                'code' => 'C02',
                'title' => 'วันที่รักษา (dttran) ไม่มีสิทธิประกันสังคม',
                'desc' => 'วันที่มารับบริการ (' . DateThai($visit->vstdate) . ') เกินกำหนดวันหมดอายุคุ้มครองของสิทธิ (' . DateThai($visit->expire_date) . ')',
                'status' => 'danger'
            ];
        }

        // 3. Audit C07: Check Main Hospital Code (Hmain)
        if (empty($visit->hospmain)) {
            $pre_audits[] = [
                'code' => 'C07',
                'title' => 'รหัสสถานพยาบาลหลักไม่ถูกต้อง',
                'desc' => 'ไม่ระบุรหัสโรงพยาบาลหลัก (Hmain) ในหน้าประวัติสิทธิการรักษาของคนไข้ จำเป็นต้องระบุรหัส 5 หลักเพื่อใช้ในการส่งออกและชดเชยค่าบริการ',
                'status' => 'danger'
            ];
        } else {
            $hmain_valid = DB::table('lookup_hospcode')
                ->where('hospcode', $visit->hospmain)
                ->where('hmain_sss', 'Y')
                ->exists();
            if (!$hmain_valid) {
                $pre_audits[] = [
                    'code' => 'C07',
                    'title' => 'รหัสสถานพยาบาลหลักไม่อยู่ในเครือข่าย',
                    'desc' => 'รหัสโรงพยาบาลหลัก (' . $visit->hospmain . ') ไม่ได้ขึ้นทะเบียนเป็นโรงพยาบาลหลักร่วมเครือข่ายของเรา อาจส่งผลให้เบิกสิทธิปกติไม่ผ่าน (ยกเว้นเป็นกรณีฉุกเฉินส่งต่อ)',
                    'status' => 'warning'
                ];
            }
        }

        // 4. Audit ICD10 CHI: Check if diagnosis codes are valid (Validate ONLY primary diagnosis/PDX)
        $validator = new \App\Services\ClaimValidator();
        $has_pdx = false;
        foreach ($diagnoses as $d) {
            if (($d->diagtype ?? '') != '1') {
                continue;
            }
            $has_pdx = true;
            $res = $validator->validateIcd10Chi($d->icd10 ?? '', '1');
            if (!$res['is_valid']) {
                $pre_audits[] = [
                    'code' => '',
                    'title' => 'รหัสวินิจฉัยหลักผิดกฎ',
                    'desc' => $res['message'] . ' (กรุณาแก้ไขรหัสโรคให้ถูกต้องใน HOSxP)',
                    'status' => 'danger'
                ];
            }
        }
        if (!$has_pdx) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)',
                'desc' => 'ไม่พบรหัสวินิจฉัยโรคหลัก (PDX) กรุณาบันทึกแพทย์ผู้ตรวจโรค',
                'status' => 'danger'
            ];
        }

        // SvPID (S15) check for SSOP
        $doc_name = !empty($visit->doctor_name) ? trim($visit->doctor_name) : 'ไม่ระบุชื่อแพทย์';
        if (empty($visit->doctor_license)) {
            $pre_audits[] = [
                'code' => 'S15',
                'title' => 'ไม่พบเลขใบประกอบวิชาชีพแพทย์ (SvPID)',
                'desc' => "กรุณาระบุเลขใบประกอบวิชาชีพในระบบ HOSxP (แพทย์ผู้รักษา: {$doc_name})",
                'status' => 'danger'
            ];
        } else {
            $lic = trim($visit->doctor_license);
            $is_valid_format = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
            if (!$is_valid_format) {
                $pre_audits[] = [
                    'code' => 'S15',
                    'title' => 'เลขที่ใบประกอบวิชาชีพ SvPID ไม่ถูกต้อง',
                    'desc' => "เลขใบประกอบวิชาชีพแพทย์ '{$lic}' มีรูปแบบไม่ถูกต้อง (แพทย์ผู้รักษา: {$doc_name}) (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลขเท่านั้น เช่น ว15245 หรือ -)",
                    'status' => 'danger'
                ];
            }
        }

        // 5. Audit BILLDISP: Pharmacist/Prescriber License and TMT ID for SSOP
        $has_dispense = !empty($drugs);
        if ($has_dispense) {
            $license = !empty($visit->doctor_license) ? trim($visit->doctor_license) : '';
            if (empty($license) || $license === '-') {
                $pre_audits[] = [
                    'code' => '',
                    'title' => 'ไม่พบเลขใบอนุญาตผู้สั่งยา/เภสัชกร',
                    'desc' => 'ไม่พบเลขใบอนุญาตประกอบวิชาชีพของแพทย์ผู้สั่งยา/จัดจ่ายยาในระบบ',
                    'status' => 'danger'
                ];
            }
            foreach ($drugs as $drug) {
                $item_prdcat = !empty($drug->sks_product_category_id) ? (string)$drug->sks_product_category_id : '';
                if (str_starts_with($drug->icode, '3')) {
                    if ($drug->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat) || !in_array($item_prdcat, ['1', '2', '3', '4', '5'])) {
                    $item_prdcat = '1';
                }
                if ($item_prdcat === '1' && empty($drug->tmtid)) {
                    $pre_audits[] = [
                        'code' => '',
                        'title' => 'ยาไม่มีรหัสมาตรฐาน TMT',
                        'desc' => "ยา {$drug->icode} ({$drug->name}) ไม่มีรหัสมาตรฐาน TMT ในแฟ้ม Drug Catalog",
                        'status' => 'danger'
                    ];
                }
            }
        }

        // 6. Audit Invoice No.
        $raw_invo = !empty($visit->sss_invno) ? $visit->sss_invno : (!empty($visit->debt_id_list) ? $visit->debt_id_list : '');
        if (empty($raw_invo)) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'ไม่พบเลขใบแจ้งหนี้ (InvNo)',
                'desc' => 'ไม่พบเลขใบแจ้งหนี้ (InvNo) กรุณากดออกใบแจ้งหนี้ใน HOSxP',
                'status' => 'danger'
            ];
        } elseif ($raw_invo === $vn) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'เลขใบแจ้งหนี้ใช้เลข VN',
                'desc' => 'เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้จริง)',
                'status' => 'danger'
            ];
        }

        // 7. Audit Claim Amount
        $uc_money = (float)($visit->uc_money ?? 0.0);
        if ($uc_money <= 0.0) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'ยอดเงินเรียกเก็บไม่ถูกต้อง',
                'desc' => 'ยอดเงินเรียกเก็บ (uc_money) น้อยกว่าหรือเท่ากับ 0 บาท',
                'status' => 'danger'
            ];
        }

        $latest_rep = DB::table('rep_sss_ssop')
            ->where('pid', $visit->cid)
            ->where('dttran_date', $visit->vstdate)
            ->where(DB::raw("LEFT(dttran_time, 5)"), substr($visit->vsttime, 0, 5))
            ->orderByDesc('id')
            ->first();
        if ($latest_rep) {
            $visit->rep_date = $latest_rep->rep_date;
            $visit->rep_time = $latest_rep->rep_time;
            $visit->rep_no = $latest_rep->rep_no;
            $visit->rep_station = $latest_rep->station;
        }

        return response()->json([
            'visit' => $visit,
            'diagnoses' => $diagnoses,
            'drugs' => $drugs,
            'rep_feedbacks' => $rep_feedbacks,
            'pre_audits' => $pre_audits
        ]);
    }

    public function csop_detail(Request $request)
    {
        $vn = $request->vn;
        if (empty($vn)) {
            return response()->json(['error' => 'Invalid VN'], 400);
        }

        $visit = DB::connection('hosxp')->selectOne('
            SELECT o.vstdate, o.vsttime, pt.hn, pt.sex, v.age_y, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, pt.cid,
                   p.name AS pttype_name, p.hipdata_code, os.cc, v.pdx, v.income, v.uc_money, IFNULL(rc.rcpt_money, 0) AS rcpt_money,
                   rc.rcpno_list, v.debt_id_list, osb.invno AS csop_invno, osb.billno AS csop_billno,
                   vp.begin_date, vp.expire_date, vp.hospmain, vp.hospsub, vp.pttypeno, v.paid_money, v.remain_money,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   doc.licenseno AS doctor_license, doc.name AS doctor_name
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money, GROUP_CONCAT(r.rcpno) AS rcpno_list
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            WHERE o.vn = ?
        ', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'Visit not found'], 404);
        }

        $diagnoses = DB::connection('hosxp')->select('
            SELECT icd10, diagtype 
            FROM ovstdiag 
            WHERE vn = ?
        ', [$vn]);

        $drugs = DB::connection('hosxp')->select("
            SELECT op.icode, COALESCE(sd.name, ni.name) AS name, op.qty, op.sum_price, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                   gt.gpu_code, gg.gp_code, op.drugusage,
                   CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text,
                   COALESCE(di.sks_product_category_id, sd.sks_product_category_id) AS sks_product_category_id, di.capacity_name, di.capacity_qty,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name, ni.nhso_adp_code,
                   op.income, inc.income_csmbs_code
            FROM opitemrece op
            LEFT JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN drugusage du ON du.drugusage = op.drugusage
            LEFT JOIN nondrugitems ni ON ni.icode = op.icode
            LEFT JOIN income inc ON inc.income = op.income
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            LEFT JOIN tmt_gpu_to_tpu gt ON gt.tpu_code = COALESCE(nd.tmtid, sd.sks_drug_code)
            LEFT JOIN tmt_gp_to_gpu gg ON gg.gpu_code = gt.gpu_code
            WHERE op.vn = ?
        ", [$vn]);

        $rep_feedbacks = [];
        $raw_records = DB::table('rep_ofc_csop')
            ->where('vn', $vn)
            ->orderByRaw("COALESCE(rep_date, '1970-01-01') DESC")
            ->orderByRaw("COALESCE(rep_time, '00:00:00') DESC")
            ->orderByRaw("COALESCE(rep_no, '') DESC")
            ->orderByRaw("COALESCE(rep_file, '') DESC")
            ->orderByDesc('id')
            ->get();

        $rep_records = [];
        $seen_stations = [];
        foreach ($raw_records as $rec) {
            $station = $rec->station ?? '';
            if (!in_array($station, $seen_stations)) {
                $seen_stations[] = $station;
                $rep_records[] = $rec;
            }
        }

        $lookup = [];
        $json_path = base_path('docs/lookup/csop_error_codes.json');
        if (file_exists($json_path)) {
            $lookup = json_decode(file_get_contents($json_path), true) ?: [];
        }
        $sss_lookup = [];
        $sss_json_path = base_path('docs/lookup/sss_error_codes.json');
        if (file_exists($sss_json_path)) {
            $sss_lookup = json_decode(file_get_contents($sss_json_path), true) ?: [];
        }

        foreach ($rep_records as $rep_record) {
            if ($rep_record->stat === 'A') {
                continue;
            }
            if ($rep_record && !empty($rep_record->error_codes)) {
                $codes = array_filter(array_map('trim', explode(',', $rep_record->error_codes)));
                foreach ($codes as $c) {
                    $desc = $lookup[$c] ?? ($sss_lookup[$c] ?? 'ไม่พบข้อมูลในคู่มือ');
                    $is_warn = str_starts_with(strtoupper($c), 'W');
                    $rep_feedbacks[] = [
                        'code' => $c,
                        'type' => $is_warn ? 'warning' : 'error',
                        'desc' => $desc
                    ];
                }
            }
        }
        $pre_audits = [];

        if (empty($visit->cid) || strlen(trim($visit->cid)) !== 13) {
            $pre_audits[] = [
                'code' => 'C01',
                'title' => 'ไม่มีสิทธิ สกส. / ข้อมูลสิทธิไม่สมบูรณ์',
                'desc' => 'ไม่พบเลขบัตรประชาชน (CID) หรือความยาวเลขบัตรไม่ครบ 13 หลัก',
                'status' => 'danger'
            ];
        }

        if (empty($visit->pdx)) {
            $pre_audits[] = [
                'code' => 'C02',
                'title' => 'ไม่มีการวินิจฉัยหลัก (PDX)',
                'desc' => 'กรุณาระบุรหัสโรคการวินิจฉัยหลัก (diagtype = 1) ในระบบ HOSxP',
                'status' => 'danger'
            ];
        } else {
            $validator = new \App\Services\ClaimValidator();
            $has_pdx = false;
            foreach ($diagnoses as $diag) {
                $icd10 = trim($diag->icd10 ?? '');
                $diagtype = trim($diag->diagtype ?? '');
                if ($diagtype === '1') {
                    $has_pdx = true;
                }
                if (!empty($icd10)) {
                    $res = $validator->validateIcd10Chi($icd10, $diagtype);
                    if (!$res['is_valid']) {
                        $pre_audits[] = [
                            'code' => 'S54',
                            'title' => "รหัสวินิจฉัย {$icd10} (ประเภท {$diagtype}) ไม่ถูกต้องตามเกณฑ์ สกส.",
                            'desc' => $res['message'] . ' (กรุณาแก้ไขรหัสโรคให้ถูกต้องใน HOSxP หรือปรับ CodeSet เป็น TT)',
                            'status' => 'danger'
                        ];
                    }
                }
            }
        }

        // SvPID (S15) check
        $doc_name = !empty($visit->doctor_name) ? trim($visit->doctor_name) : 'ไม่ระบุชื่อแพทย์';
        if (empty($visit->doctor_license)) {
            $pre_audits[] = [
                'code' => 'S15',
                'title' => 'ไม่พบเลขใบประกอบวิชาชีพแพทย์ (SvPID)',
                'desc' => "กรุณาระบุเลขใบประกอบวิชาชีพในระบบ HOSxP (แพทย์ผู้รักษา: {$doc_name})",
                'status' => 'danger'
            ];
        } else {
            $lic = trim($visit->doctor_license);
            $is_valid_format = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
            if (!$is_valid_format) {
                $pre_audits[] = [
                    'code' => 'S15',
                    'title' => 'เลขที่ใบประกอบวิชาชีพ SvPID ไม่ถูกต้อง',
                    'desc' => "เลขใบประกอบวิชาชีพแพทย์ '{$lic}' มีรูปแบบไม่ถูกต้อง (แพทย์ผู้รักษา: {$doc_name}) (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลขเท่านั้น เช่น ว15245 หรือ -)",
                    'status' => 'danger'
                ];
            }
        }

        // CSOP outpatient service validation rules (T72, T78, T84)
        $map_income_to_csop_group = function($csmbs_code, $fallback_income) {
            if (empty($csmbs_code)) {
                $fallback_income = str_pad($fallback_income, 2, '0', STR_PAD_LEFT);
                switch ($fallback_income) {
                    case '01': return '1';
                    case '02': return '2';
                    case '03':
                    case '04':
                    case '17': return '3';
                    case '05': return '5';
                    case '06': return '6';
                    case '07': return '7';
                    case '08': return '8';
                    case '09': return '9';
                    case '10': return 'A';
                    case '11': return 'B';
                    case '12':
                    case '18': return 'C';
                    case '13': return 'D';
                    case '14': return 'E';
                    case '15': return 'F';
                    case '16': return 'H';
                    default: return 'G';
                }
            }
            switch (trim($csmbs_code)) {
                case '01': return '1';
                case '02': return '2';
                case '03':
                case '04': return '3';
                case '05': return '5';
                case '06': return '6';
                case '07': return '7';
                case '08': return '8';
                case '09': return '9';
                case '10': return 'A';
                case '11': return 'B';
                case '12': return 'C';
                case '13': return 'D';
                case '14': return 'E';
                case '15': return 'F';
                case '16': return 'H';
                case '17': return 'I';
                default: return 'G';
            }
        };

        $has_op_service_fee = false;
        $has_room_fee = false;
        $other_groups = [];

        foreach ($drugs as $item) {
            $adp = trim($item->nhso_adp_code ?? '');
            if ($adp === '55020' || $adp === '55021') {
                $has_op_service_fee = true;
                continue;
            }

            $g = $map_income_to_csop_group($item->income_csmbs_code, $item->income);
            if ($g === '2') {
                $has_room_fee = true;
            }
            $other_groups[$g] = true;
        }

        if ($has_op_service_fee) {
            // Rule T72: Outpatient fee + room observation fee together
            if ($has_room_fee) {
                $pre_audits[] = [
                    'code' => 'T72',
                    'title' => 'เบิกค่าบริการผู้ป่วยนอกร่วมกับค่าเตียงสังเกตอาการ',
                    'desc' => 'มีการเบิกค่าบริการผู้ป่วยนอก (55020/55021) ร่วมกับค่าเตียงสังเกตอาการ (หมวด 2) สกส. จะตรวจติด C รหัส T72',
                    'status' => 'danger'
                ];
            }

            // Rule T78: Outpatient fee + Group 15 (F) acupuncture only, no other groups
            if (count($other_groups) === 1 && isset($other_groups['F'])) {
                $pre_audits[] = [
                    'code' => 'T78',
                    'title' => 'เบิกค่าบริการผู้ป่วยนอกร่วมกับบริการฝังเข็มหมวด 15 เท่านั้น',
                    'desc' => 'มีการเบิกค่าบริการผู้ป่วยนอกร่วมกับบริการฝังเข็ม (หมวด 15/F) โดยไม่มีค่ารักษาหมวดอื่นร่วมด้วย สกส. จะตรวจติด C รหัส T78',
                    'status' => 'danger'
                ];
            }

            // Rule T84: Outpatient fee, no doctor visit (SVPid not starting with ว)
            // AND only scheduled procedures/investigations (Groups 8, B, E, F only)
            $allowed_t84_groups = ['8', 'B', 'E', 'F'];
            $only_t84_groups = true;
            $has_any_item = !empty($other_groups);

            foreach (array_keys($other_groups) as $g) {
                if (!in_array($g, $allowed_t84_groups)) {
                    $only_t84_groups = false;
                }
            }

            $has_doctor = false;
            if (!empty($visit->doctor_license)) {
                $lic = strtoupper(trim($visit->doctor_license));
                if (str_starts_with($lic, 'ว')) {
                    $has_doctor = true;
                }
            }

            if ($has_any_item && $only_t84_groups && !$has_doctor) {
                $pre_audits[] = [
                    'code' => 'T84',
                    'title' => 'เบิกค่าบริการผู้ป่วยนอกโดยไม่มีประวัติพบแพทย์ (เฉพาะทำหัตถการ)',
                    'desc' => 'มีการเบิกค่าบริการผู้ป่วยนอกและหัตถการ (หมวด 8, B, E, F) แต่ไม่มีประวัติการพบแพทย์ (ใบอนุญาตแพทย์ไม่ได้ขึ้นต้นด้วย ว.) สกส. จะตรวจติด C รหัส T84',
                    'status' => 'danger'
                ];
            }
        }

        // CSOP Pharmacist License and TMT ID checks
        $has_dispense = !empty($drugs);
        if ($has_dispense) {
            $license = !empty($visit->doctor_license) ? trim($visit->doctor_license) : '';
            if (empty($license) || $license === '-') {
                $pre_audits[] = [
                    'code' => '',
                    'title' => 'ไม่พบเลขใบอนุญาตผู้สั่งยา/เภสัชกร',
                    'desc' => 'ไม่พบเลขใบอนุญาตประกอบวิชาชีพของแพทย์ผู้สั่งยา/จัดจ่ายยาในระบบ',
                    'status' => 'danger'
                ];
            }
            foreach ($drugs as $drug) {
                $item_prdcat = !empty($drug->sks_product_category_id) ? (string)$drug->sks_product_category_id : '';
                if (str_starts_with($drug->icode, '3')) {
                    if ($drug->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat) || !in_array($item_prdcat, ['1', '2', '3', '4', '5'])) {
                    $item_prdcat = '1';
                }
                if ($item_prdcat === '1' && empty($drug->tmtid)) {
                    $pre_audits[] = [
                        'code' => '',
                        'title' => 'ยาไม่มีรหัสมาตรฐาน TMT',
                        'desc' => "ยา {$drug->icode} ({$drug->name}) ไม่มีรหัสมาตรฐาน TMT ในแฟ้ม Drug Catalog",
                        'status' => 'danger'
                    ];
                }
            }
        }

        // Audit Invoice No for CSOP
        $raw_invo = !empty($visit->csop_invno) ? $visit->csop_invno : (!empty($visit->debt_id_list) ? $visit->debt_id_list : '');
        if (empty($raw_invo)) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'ไม่พบเลขใบแจ้งหนี้ (InvNo)',
                'desc' => 'ไม่พบเลขใบแจ้งหนี้ (InvNo) กรุณากดออกใบแจ้งหนี้ใน HOSxP',
                'status' => 'danger'
            ];
        } elseif ($raw_invo === $vn) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'เลขใบแจ้งหนี้ใช้เลข VN',
                'desc' => 'เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้จริง)',
                'status' => 'danger'
            ];
        }

        // Audit Claim Amount for CSOP
        $uc_money = (float)($visit->uc_money ?? 0.0);
        if ($uc_money <= 0.0) {
            $pre_audits[] = [
                'code' => '',
                'title' => 'ยอดเงินเรียกเก็บไม่ถูกต้อง',
                'desc' => 'ยอดเงินเรียกเก็บ (uc_money) น้อยกว่าหรือเท่ากับ 0 บาท',
                'status' => 'danger'
            ];
        }

        $latest_rep = DB::table('rep_sss_ssop')
            ->where('pid', $visit->cid)
            ->where('dttran_date', $visit->vstdate)
            ->where(DB::raw("LEFT(dttran_time, 5)"), substr($visit->vsttime, 0, 5))
            ->orderByDesc('id')
            ->first();
        if ($latest_rep) {
            $visit->rep_date = $latest_rep->rep_date;
            $visit->rep_time = $latest_rep->rep_time;
            $visit->rep_no = $latest_rep->rep_no;
            $visit->rep_station = $latest_rep->station;
        }

        return response()->json([
            'visit' => $visit,
            'diagnoses' => $diagnoses,
            'drugs' => $drugs,
            'rep_feedbacks' => $rep_feedbacks,
            'pre_audits' => $pre_audits
        ]);
    }

    public function sss_chronic_import(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('sss_chronic_');
        $extractPath = storage_path('app/tmp_sss_chronic_import/' . $uniqueId);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไฟล์ ZIP เสียหาย (ไม่สามารถเปิดไฟล์ได้)'
                ], 400);
            }

            if (!\File::exists($extractPath)) {
                \File::makeDirectory($extractPath, 0755, true);
            }

            $zip->extractTo($extractPath);
            $zip->close();
        } catch (\Throwable $e) {
            if (\File::exists($extractPath)) {
                \File::deleteDirectory($extractPath);
            }
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแตกไฟล์ ZIP: ' . $e->getMessage()
            ], 400);
        }

        try {
            $files = \File::files($extractPath);
        } catch (\Throwable $e) {
            if (\File::exists($extractPath)) {
                \File::deleteDirectory($extractPath);
            }
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอ่านไฟล์ด้านใน ZIP ได้: ' . $e->getMessage()
            ], 400);
        }
        $processedCount = 0;
        $newTpuCount = 0;
        $newDxCount = 0;
        $warnings = [];

        $new_tpu_entries = [];
        $new_dx_entries = [];

        foreach ($files as $f) {
            if (strtolower($f->getExtension()) === 'txt') {
                try {
                    $fileName = $f->getFilename();
                    // Prevent duplicate data by deleting existing records of the same file name first
                    DB::table('sss_chronic')->where('rep_file', $fileName)->delete();
                    $contentBytes = \File::get($f->getRealPath());
                    
                    // Convert encoding from Windows-874 to UTF-8
                    $content = @iconv('Windows-874', 'UTF-8//IGNORE', $contentBytes);
                    if ($content === false || ($content === '' && $contentBytes !== '')) {
                        try {
                            $supported = mb_list_encodings();
                            $from_enc = 'auto';
                            if (in_array('Windows-874', $supported)) {
                                $from_enc = 'Windows-874';
                            } elseif (in_array('TIS-620', $supported)) {
                                $from_enc = 'TIS-620';
                            } elseif (in_array('ISO-8859-11', $supported)) {
                                $from_enc = 'ISO-8859-11';
                            }
                            $content = mb_convert_encoding($contentBytes, 'UTF-8', $from_enc);
                        } catch (\Throwable $e) {
                            $content = $contentBytes;
                        }
                    }

                    $lines = explode("\n", $content);
                    $current_section = null;

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }

                        // Identify current section
                        if (str_contains($line, 'ตอนที่ 1')) {
                            $current_section = '1';
                            continue;
                        } elseif (str_contains($line, 'ตอนที่ 2.1')) {
                            $current_section = '2.1';
                            continue;
                        } elseif (str_contains($line, 'ตอนที่ 2.2')) {
                            $current_section = '2.2';
                            continue;
                        }

                        // Parse data row using CSV reader
                        $parts = str_getcsv($line);
                        if (count($parts) >= 10 && is_numeric(trim($parts[0]))) {
                            $repline = trim($parts[1]);
                            $hcode = trim($parts[2]);
                            $hmain = trim($parts[3]);
                            $invno = trim($parts[4]);
                            $hn = trim($parts[5]);
                            $pid = trim($parts[6]);
                            $dttran = trim($parts[7]);
                            $dx = trim($parts[8]);
                            $drug = trim($parts[9]);

                            if ($current_section !== null) {
                                // Insert to database (using DB query builder)
                                DB::table('sss_chronic')->insert([
                                    'rep_file' => $fileName,
                                    'repline' => is_numeric($repline) ? (int)$repline : null,
                                    'hcode' => $hcode,
                                    'hmain' => $hmain,
                                    'invno' => $invno,
                                    'hn' => $hn,
                                    'pid' => $pid,
                                    'dttran' => $dttran,
                                    'section_type' => $current_section,
                                    'dx' => $dx,
                                    'drug' => $drug,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                $processedCount++;

                                // Auto-learning logic for Section 1 (ตอนที่ 1)
                                if ($current_section === '1') {
                                    // Extract TMT drug codes with category mapping
                                    if (preg_match('/^(\d+)\s*\(([^)]+)\)/', $drug, $drugMatches)) {
                                        $cat_str = str_pad(ltrim($drugMatches[1], '0'), 2, '0', STR_PAD_LEFT);
                                        $codes = explode(',', $drugMatches[2]);
                                        foreach ($codes as $c) {
                                            $c = trim($c);
                                            if (is_numeric($c)) {
                                                $new_tpu_entries[] = [
                                                    'cat' => $cat_str,
                                                    'tpu' => $c
                                                ];
                                            }
                                        }
                                    }

                                    // Extract ICD-10 disease codes
                                    if (preg_match('/^(\d+)\s*\(([^)]+)\)/', $dx, $dxMatches)) {
                                        $cat_str = str_pad(ltrim($dxMatches[1], '0'), 2, '0', STR_PAD_LEFT);
                                        $icd_codes = explode(',', $dxMatches[2]);
                                        foreach ($icd_codes as $icd) {
                                            $icd = strtoupper(str_replace('.', '', trim($icd)));
                                            if ($icd !== '') {
                                                $new_dx_entries[] = [
                                                    'cat' => $cat_str,
                                                    'icd' => $icd
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $txtException) {
                    $warnings[] = "ไฟล์ย่อย " . $f->getFilename() . " ผิดพลาด: " . $txtException->getMessage();
                    continue;
                }
            }
        }

        // Clean up temporary extracted directory
        \File::deleteDirectory($extractPath);

        // Update tmt_sss_chronic.json with new drug codes
        $tmt_json_path = storage_path('app/tmt_sss_chronic.json');
        if (!file_exists($tmt_json_path)) {
            $default_tmt = base_path('docs/lookup/tmt_sss_chronic.json');
            if (file_exists($default_tmt)) {
                @copy($default_tmt, $tmt_json_path);
            }
        }
        if (file_exists($tmt_json_path) && !empty($new_tpu_entries)) {
            $tmt_data = json_decode(file_get_contents($tmt_json_path), true);
            $diseases = $tmt_data['diseases'] ?? [];
            $updated = false;
            foreach ($new_tpu_entries as $entry) {
                $cat = $entry['cat'];
                $tpu = $entry['tpu'];
                foreach ($diseases as &$dis) {
                    $dis_id_prefix = substr($dis['id'], 0, 2);
                    if (str_pad($dis_id_prefix, 2, '0', STR_PAD_LEFT) === $cat) {
                        if (!in_array($tpu, $dis['tpu_codes'] ?? [])) {
                            $dis['tpu_codes'][] = $tpu;
                            $newTpuCount++;
                            $updated = true;
                        }
                    }
                }
            }
            if ($updated) {
                $tmt_data['diseases'] = $diseases;
                file_put_contents($tmt_json_path, json_encode($tmt_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        // Update icd10_sss_chronic.json with new disease codes
        $ncd_json_path = storage_path('app/icd10_sss_chronic.json');
        if (!file_exists($ncd_json_path)) {
            $default_ncd = base_path('docs/lookup/icd10_sss_chronic.json');
            if (file_exists($default_ncd)) {
                @copy($default_ncd, $ncd_json_path);
            }
        }
        if (file_exists($ncd_json_path) && !empty($new_dx_entries)) {
            $ncd_data = json_decode(file_get_contents($ncd_json_path), true);
            $diseases = $ncd_data['diseases'] ?? [];
            $root_prefixes = $ncd_data['prefixes'] ?? [];
            $ncd_updated = false;

            foreach ($new_dx_entries as $entry) {
                $cat = $entry['cat'];
                $icd = $entry['icd'];

                foreach ($diseases as &$dis) {
                    $dis_id_prefix = substr($dis['id'], 0, 2);
                    if (str_pad($dis_id_prefix, 2, '0', STR_PAD_LEFT) === $cat) {
                        if (!in_array($icd, $dis['prefixes'])) {
                            $dis['prefixes'][] = $icd;
                            $ncd_updated = true;
                        }
                    }
                }

                if (!isset($root_prefixes[$icd])) {
                    $root_prefixes[$icd] = true;
                    $newDxCount++;
                    $ncd_updated = true;
                }
            }

            if ($ncd_updated) {
                $ncd_data['diseases'] = $diseases;
                $ncd_data['prefixes'] = $root_prefixes;
                file_put_contents($ncd_json_path, json_encode($ncd_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        return response()->json([
            'success' => true,
            'message' => "นำเข้าข้อมูลสำเร็จ นำเข้าได้ทั้งหมด {$processedCount} รายการ (เรียนรู้รหัสยาใหม่ {$newTpuCount} ตัว, รหัสโรคใหม่ {$newDxCount} รหัส)",
            'warnings' => $warnings
        ]);
    }

    public function sss_chronic_list()
    {
        $list21 = DB::table('sss_chronic')
            ->where('section_type', '2.1')
            ->orderByDesc('dttran')
            ->limit(300)
            ->get();

        $list22 = DB::table('sss_chronic')
            ->where('section_type', '2.2')
            ->orderByDesc('dttran')
            ->limit(300)
            ->get();

        // Filter out from list22 any patient who is already in list21 (by PID)
        $pidsIn21 = $list21->pluck('pid')->filter()->unique()->toArray();
        $filteredList22 = [];
        foreach ($list22 as $row) {
            if (!empty($row->pid) && in_array($row->pid, $pidsIn21)) {
                continue; // Skip as they are already registered (exist in 2.1)
            }
            $filteredList22[] = $row;
        }
        $list22 = collect($filteredList22);

        $hns = $list21->pluck('hn')->merge($list22->pluck('hn'))->unique()->filter()->toArray();

        $patients = [];
        if (!empty($hns)) {
            try {
                $patients = DB::connection('hosxp')->table('patient')
                    ->select('hn', DB::raw("CONCAT(pname, fname, ' ', lname) AS ptname"))
                    ->whereIn('hn', $hns)
                    ->get()
                    ->keyBy('hn')
                    ->toArray();
            } catch (\Throwable $e) {
                // If hosxp connection fails, fallback gracefully without patient names
            }
        }

        foreach ($list21 as $row) {
            $row->ptname = isset($patients[$row->hn]) ? $patients[$row->hn]->ptname : '-';
        }

        foreach ($list22 as $row) {
            $row->ptname = isset($patients[$row->hn]) ? $patients[$row->hn]->ptname : '-';
        }

        return response()->json([
            'list21' => $list21,
            'list22' => $list22
        ]);
    }

    public function csop_31(Request $request)
    {
        ini_set('max_execution_time', 0);

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
        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        } else {
            $end_date_b = DB::table('budget_year')
                ->where('LEAVE_YEAR_ID', $budget_year)
                ->value('DATE_END');
        }

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // Dynamic CSOP pttypes query
        $csop_pttypes = DB::connection('hosxp')
            ->table('pttype as p')
            ->join('sks_benefit_plan_type as sks', 'sks.sks_benefit_plan_type_id', '=', 'p.sks_benefit_plan_type_id')
            ->join('pttype_upp_type as put', 'put.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
            ->where('sks.sks_code', 'CS')
            ->where('put.pttype_upp_type_code', '31')
            ->pluck('p.pttype')
            ->toArray();

        if (empty($csop_pttypes)) {
            $csop_pttypes = [''];
        }
        $csop_pttypes_str = "'" . implode("','", array_map(function($x) { return str_replace("'", "\\'", $x); }, $csop_pttypes)) . "'";

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_op.csop_31', compact(
                'budget_year_select',
                'budget_year',
                'start_date',
                'end_date'
            ));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(claim_sent_price,0)) AS claim_sent_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,
                  IFNULL(csop.amount, 0) AS receive_total,
                  CASE WHEN (csop.hn IS NOT NULL OR rep.vn IS NOT NULL) THEN (v.income-IFNULL(rc.rcpt_money, 0)) ELSE 0 END AS claim_sent_price
            FROM ovst o            
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN (
			    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
			    FROM rcpt_print r
			    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
			    WHERE a.rcpno IS NULL
			    GROUP BY r.vn
			) rc ON rc.vn = o.vn
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount
                FROM hrims.stm_ofc_csop 
                WHERE sys <> "HD" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)
            LEFT JOIN (
                SELECT vn
                FROM hrims.rep_ofc_csop
                WHERE dttran_date BETWEEN ? AND ?
                GROUP BY vn
            ) rep ON rep.vn = o.vn
            WHERE p.pttype IN (' . $csop_pttypes_str . ')
                AND (o.an = "" OR o.an IS NULL)
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq,o.vn,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain, vp.pttype AS sss_pttype,
            pt.cid, vp.begin_date, vp.expire_date,
            os.cc,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            COALESCE((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND pttype = vp.pttype), v.income) AS income, v.uc_money, 
            IFNULL((SELECT SUM(r.total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND r.pttype = vp.pttype AND a.rcpno IS NULL), 0) AS rcpt_money, 
            d.receive AS receive_total,
            v.debt_id_list, osb.invno AS csop_invno, osb.billno AS csop_billno,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            doc.licenseno AS doctor_license
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            LEFT JOIN hrims.debtor_1102050101_301 d ON d.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            WHERE p.pttype IN (' . $csop_pttypes_str . ')
            AND (o.an = "" OR o.an IS NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        $vns = array_column($claim, 'vn');
        $rep_records = [];
        $stm_pays = [];
        if (!empty($vns)) {
            // Fetch latest REP records grouped by vn and station sorted chronologically for CSOP
            $raw_rep_records = DB::table('rep_ofc_csop')
                ->whereIn('vn', $vns)
                ->whereIn('id', function($query) use ($vns) {
                    $query->select('id')
                        ->from(DB::raw("(
                            SELECT id, vn, station,
                                   ROW_NUMBER() OVER (
                                       PARTITION BY vn, station 
                                       ORDER BY 
                                           COALESCE(rep_date, '1970-01-01') DESC, 
                                           COALESCE(rep_time, '00:00:00') DESC, 
                                           COALESCE(rep_no, '') DESC, 
                                           COALESCE(rep_file, '') DESC, 
                                           id DESC
                                   ) as rn
                            FROM rep_ofc_csop
                            WHERE vn IN ('" . implode("','", $vns) . "')
                        ) t"))
                        ->where('rn', 1);
                })
                ->get();

            // Fetch only VNs in this set that ever had status 'A' for CSOP (using index is very fast)
            $passed_a_vns = DB::table('rep_ofc_csop')
                ->whereIn('vn', $vns)
                ->where('stat', 'A')
                ->distinct()
                ->pluck('vn')
                ->toArray();
            
            $passed_a_map = array_flip($passed_a_vns);

            $rep_records = [];
            foreach ($raw_rep_records as $rec) {
                if ($rec->stat === 'A' || isset($passed_a_map[$rec->vn])) {
                    if (!isset($rep_records[$rec->vn])) {
                        $dummy = clone $rec;
                        $dummy->error_codes = '';
                        $rep_records[$rec->vn] = $dummy; // Mark as has REP but no errors
                    }
                    continue;
                }
                if (isset($rep_records[$rec->vn])) {
                    // Combine error codes if multiple claim types exist for the same vn
                    if (!empty($rec->error_codes)) {
                        $existing_codes = array_filter(array_map('trim', explode(',', $rep_records[$rec->vn]->error_codes ?? '')));
                        $new_codes = array_filter(array_map('trim', explode(',', $rec->error_codes)));
                        $combined = array_unique(array_merge($existing_codes, $new_codes));
                        $rep_records[$rec->vn]->error_codes = implode(',', $combined);
                    }
                } else {
                    $rep_records[$rec->vn] = $rec;
                }
            }

            foreach ($rep_records as $vn_key => $rep_rec) {
                if (!empty($rep_rec->error_codes)) {
                    $codes = array_filter(array_map('trim', explode(',', $rep_rec->error_codes)));
                    $filtered_codes = array_filter($codes, function($c) {
                        $u = strtoupper(trim($c));
                        return $u !== 'S01' && $u !== 'T02' && $u !== 'R01';
                    });
                    $rep_rec->error_codes = !empty($filtered_codes) ? implode(',', $filtered_codes) : '';
                }
            }

            $stm_records = DB::table('stm_ofc_csop')
                ->select('hn', 'vstdate', 'vsttime', 'amount')
                ->whereIn('hn', array_column($claim, 'hn'))
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->get();
            foreach ($stm_records as $rec) {
                $key = $rec->hn . '_' . $rec->vstdate . '_' . substr($rec->vsttime, 0, 5);
                $stm_pays[$key] = $rec->amount;
            }

            // Query drugs/items by VN for CSOP list validation
            $drugs_by_vn = [];
            $drugs = DB::connection('hosxp')->select("
                SELECT op.vn, op.icode, COALESCE(sd.name, ni.name) AS name, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                       gt.gpu_code, gg.gp_code, COALESCE(di.sks_product_category_id, sd.sks_product_category_id) AS sks_product_category_id, di.capacity_name, di.capacity_qty,
                       op.drugusage, op.qty, op.income, ni.nhso_adp_code, inc.income_csmbs_code
                FROM opitemrece op
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                LEFT JOIN drugitems di ON di.icode = op.icode
                LEFT JOIN nondrugitems ni ON ni.icode = op.icode
                LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                    AND nd.date_approved = (
                        SELECT MAX(nd1.date_approved) 
                        FROM hrims.drugcat_chi nd1 
                        WHERE nd.hospdrugcode = nd1.hospdrugcode 
                        AND nd1.updateflag IN ('A','U','E')
                    )
                LEFT JOIN tmt_gpu_to_tpu gt ON gt.tpu_code = COALESCE(nd.tmtid, sd.sks_drug_code)
                LEFT JOIN tmt_gp_to_gpu gg ON gg.gpu_code = gt.gpu_code
                LEFT JOIN income inc ON inc.income = op.income
                WHERE op.vn IN ('" . implode("','", $vns) . "')
            ");
            foreach ($drugs as $d) {
                $drugs_by_vn[$d->vn][] = $d;
            }
        }

        $search = [];
        $claim_sent = [];
        $warning = [];

        // Audit check status
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $rep = $rep_records[$row->vn] ?? null;
            $row->rep_error = $rep ? $rep->error_codes : null;
            $row->rep_warning = null;
            
            $key = $row->hn . '_' . $row->vstdate . '_' . substr($row->vsttime, 0, 5);
            $row->stm_pay = $stm_pays[$key] ?? null;
            
            $has_inv = (($row->csop_invno && $row->csop_invno !== '0') || ($row->debt_id_list && $row->debt_id_list !== '0'));
            $has_pdx = !empty($row->pdx);
            $has_cc = !empty($row->cc);
            $has_cid = (!empty($row->cid) && strlen($row->cid) === 13);
            
            $has_icd10_chi_error = false;
            $validator = new \App\Services\ClaimValidator();
            if (!empty($row->pdx)) {
                $res = $validator->validateIcd10Chi($row->pdx, '1');
                if (!$res['is_valid']) {
                    $has_icd10_chi_error = true;
                }
            }
            if (!$has_icd10_chi_error && !empty($row->sdx)) {
                $sdxs = explode(',', $row->sdx);
                foreach ($sdxs as $s) {
                    $res = $validator->validateIcd10Chi(trim($s), '3');
                    if (!$res['is_valid']) {
                        $has_icd10_chi_error = true;
                        break;
                    }
                }
            }
            if (!$has_icd10_chi_error && !empty($row->icd9)) {
                $icd9s = explode(',', $row->icd9);
                foreach ($icd9s as $i) {
                    $res = $validator->validateIcd10Chi(trim($i), '2');
                    if (!$res['is_valid']) {
                        $has_icd10_chi_error = true;
                        break;
                    }
                }
            }

            $has_svpid_error = false;
            if (empty($row->doctor_license)) {
                $has_svpid_error = true;
            } else {
                $lic = trim($row->doctor_license);
                $is_valid_format = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
                if (!$is_valid_format) {
                    $has_svpid_error = true;
                }
            }

            $visit_drugs = $drugs_by_vn[$row->vn] ?? [];

            // Check Pharmacist license presence (if drugs exist) for CSOP
            $has_valid_pharmacist = true;
            if (!empty($visit_drugs)) {
                $lic = !empty($row->doctor_license) ? trim($row->doctor_license) : '';
                if (empty($lic) || $lic === '-') {
                    $has_valid_pharmacist = false;
                }
            }

            // Check TMT ID for modern medicines for CSOP
            $has_tmt_error = false;
            foreach ($visit_drugs as $drug) {
                $item_prdcat = !empty($drug->sks_product_category_id) ? (string)$drug->sks_product_category_id : '';
                if (str_starts_with($drug->icode, '3')) {
                    if (isset($drug->income) && $drug->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat) || !in_array($item_prdcat, ['1', '2', '3', '4', '5'])) {
                    $item_prdcat = '1';
                }
                if ($item_prdcat === '1' && empty($drug->tmtid)) {
                    $has_tmt_error = true;
                    break;
                }
            }

            // CSOP outpatient service validation rules (T72, T78, T84)
            $map_income_to_csop_group = function($csmbs_code, $fallback_income) {
                if (empty($csmbs_code)) {
                    $fallback_income = str_pad($fallback_income, 2, '0', STR_PAD_LEFT);
                    switch ($fallback_income) {
                        case '01': return '1';
                        case '02': return '2';
                        case '03':
                        case '04':
                        case '17': return '3';
                        case '05': return '5';
                        case '06': return '6';
                        case '07': return '7';
                        case '08': return '8';
                        case '09': return '9';
                        case '10': return 'A';
                        case '11': return 'B';
                        case '12':
                        case '18': return 'C';
                        case '13': return 'D';
                        case '14': return 'E';
                        case '15': return 'F';
                        case '16': return 'H';
                        default: return 'G';
                    }
                }
                switch (trim($csmbs_code)) {
                    case '01': return '1';
                    case '02': return '2';
                    case '03':
                    case '04': return '3';
                    case '05': return '5';
                    case '06': return '6';
                    case '07': return '7';
                    case '08': return '8';
                    case '09': return '9';
                    case '10': return 'A';
                    case '11': return 'B';
                    case '12': return 'C';
                    case '13': return 'D';
                    case '14': return 'E';
                    case '15': return 'F';
                    case '16': return 'H';
                    case '17': return 'I';
                    default: return 'G';
                }
            };

            $has_op_service_fee = false;
            $has_room_fee = false;
            $other_groups = [];

            foreach ($visit_drugs as $item) {
                $adp = trim($item->nhso_adp_code ?? '');
                if ($adp === '55020' || $adp === '55021') {
                    $has_op_service_fee = true;
                    continue;
                }

                $g = $map_income_to_csop_group($item->income_csmbs_code, $item->income);
                if ($g === '2') {
                    $has_room_fee = true;
                }
                $other_groups[$g] = true;
            }

            $has_csop_rule_error = false;
            if ($has_op_service_fee) {
                // Rule T72: Outpatient fee + room observation fee together
                if ($has_room_fee) {
                    $has_csop_rule_error = true;
                }

                // Rule T78: Outpatient fee + Group 15 (F) acupuncture only, no other groups
                if (count($other_groups) === 1 && isset($other_groups['F'])) {
                    $has_csop_rule_error = true;
                }

                // Rule T84: Outpatient fee, no doctor visit (SVPid not starting with ว)
                // AND only scheduled procedures/investigations (Groups 8, B, E, F only)
                $allowed_t84_groups = ['8', 'B', 'E', 'F'];
                $only_t84_groups = true;
                $has_any_item = !empty($other_groups);

                foreach (array_keys($other_groups) as $g) {
                    if (!in_array($g, $allowed_t84_groups)) {
                        $only_t84_groups = false;
                    }
                }

                $has_doctor = false;
                if (!empty($row->doctor_license)) {
                    $lic = strtoupper(trim($row->doctor_license));
                    if (str_starts_with($lic, 'ว')) {
                        $has_doctor = true;
                    }
                }

                if ($has_any_item && $only_t84_groups && !$has_doctor) {
                    $has_csop_rule_error = true;
                }
            }

            if (!$has_cid || !$has_pdx || $has_icd10_chi_error || $has_svpid_error || !$has_valid_pharmacist || $has_tmt_error || !$has_inv || $has_csop_rule_error) {
                $row->claim_status = 'red';
            } else {
                $row->claim_status = 'green';
            }

            if ($row->rep_error) {
                $warning[] = $row;
            } elseif ($rep || $row->stm_pay) {
                $claim_sent[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $claim_data = $claim; // Keep original for reference if needed
        $claim = $claim_sent; // Assign sent claim to $claim variable

        if ($request->ajax() || $request->wantsJson()) {
            $table_html = view('claim_op.csop_31_table', compact(
                'budget_year', 'start_date', 'end_date', 'search', 'claim', 'warning'
            ))->render();

            return response()->json([
                'success' => true,
                'table_html' => $table_html,
                'chart_data' => !$request->input('skip_chart') ? [
                    'month' => $month,
                    'claim_price' => $claim_price,
                    'claim_sent_price' => $claim_sent_price,
                    'receive_total' => $receive_total
                ] : null
            ]);
        }

        return view('claim_op.csop_31', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim', 'warning'));
    }

    public function csop_rep_errors(Request $request)
    {
        $errors = DB::connection('hosxp')->select("
            SELECT rep.vn, rep.hn, CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname, 
                   rep.rep_file, rep.rep_no AS repno, rep.rep_date, rep.rep_time, rep.error_codes, rep.stat,
                   rep.dttran_date AS vstdate
            FROM (
                SELECT * FROM (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY vn ORDER BY COALESCE(rep_date, '1970-01-01') DESC, COALESCE(rep_time, '00:00:00') DESC, COALESCE(rep_no, '') DESC, COALESCE(rep_file, '') DESC, id DESC) as rn
                    FROM hrims.rep_ofc_csop
                ) t1 WHERE t1.rn = 1
            ) rep
            LEFT JOIN patient pt ON pt.hn = rep.hn
            LEFT JOIN hrims.stm_ofc_csop stm ON stm.hn = rep.hn AND stm.vstdate = rep.dttran_date AND LEFT(stm.vsttime, 5) = LEFT(rep.dttran_time, 5)
            WHERE rep.error_codes IS NOT NULL 
              AND rep.error_codes <> ''
              AND stm.hn IS NULL
            ORDER BY rep.id DESC
            LIMIT 500
        ");

        return response()->json([
            'success' => true,
            'data' => $errors
        ]);
    }

    public function sss_rep_errors(Request $request)
    {
        $errors = DB::connection('hosxp')->select("
            SELECT rep.vn, rep.hn, CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname, 
                   rep.rep_file, rep.repno, rep.rep_date, rep.rep_time, rep.error_codes, rep.stat,
                   rep.dttran_date AS vstdate
            FROM (
                SELECT * FROM (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY vn ORDER BY COALESCE(rep_date, '1970-01-01') DESC, COALESCE(rep_time, '00:00:00') DESC, COALESCE(repno, '') DESC, COALESCE(rep_file, '') DESC, id DESC) as rn
                    FROM hrims.rep_sss_ssop
                ) t1 WHERE t1.rn = 1
            ) rep
            LEFT JOIN patient pt ON pt.hn = rep.hn
            LEFT JOIN hrims.stm_sss_ssop stm ON stm.hn = rep.hn AND stm.vstdate = rep.dttran_date AND LEFT(stm.vsttime, 5) = LEFT(rep.dttran_time, 5)
            WHERE rep.error_codes IS NOT NULL 
              AND rep.error_codes <> ''
              AND stm.hn IS NULL
            ORDER BY rep.id DESC
            LIMIT 500
        ");

        return response()->json([
            'success' => true,
            'data' => $errors
        ]);
    }
}
