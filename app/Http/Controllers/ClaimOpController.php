<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,claim_items.total_price AS claim_price,stm.receive_total,
                  CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL THEN claim_items.total_price ELSE 0 END AS claim_sent_price
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
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")                
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
			AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
			AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');


        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,claim_items.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(claim_items.claim_price, 0) AS claim_price,
            claim_items.project,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
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
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND oe.moph_finance_upload_status IS NULL 
            AND fdh.seq IS NULL
            AND ec.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,
            o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            o.vn AS seq,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            claim_items.project,
            stm.receive_total,stm.repno,fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,MAX(ec.check_detail) AS check_detail,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
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
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND (oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL )
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

        return view('claim_op.ucs_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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
                   vp.request_funds
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
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
                   li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(d3.ref_code, d.sks_drug_code) AS tmtid
            FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?
            AND (li.uc_cr = "Y" OR li.ppfs = "Y" OR li.herb32 = "Y")', [$vn]);

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,claim_items.total_price AS claim_price,stm.receive_total,
                  CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL THEN claim_items.total_price ELSE 0 END AS claim_sent_price
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
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")                
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN ( 
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
			AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
			AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');


        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,claim_items.claim_list,
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(claim_items.claim_price, 0) AS claim_price,
            claim_items.project,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype

            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
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
                SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)
            ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND oe.moph_finance_upload_status IS NULL 
            AND fdh.seq IS NULL
            AND ec.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            vp.confirm_and_locked,vp.request_funds,
            o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            o.vn AS seq,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            claim_items.project,
            stm.receive_total,stm.repno,fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,MAX(ec.check_detail) AS check_detail,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            ep.claim_status, pt.cid,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype

            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn
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
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND (oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL )
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

        return view('claim_op.ucs_inprovince', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince_va(Request $request)
    {
        ini_set('max_execution_time', 0); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

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
				    WHEN lh.hmain_sss = "Y" THEN 370
				    ELSE 120
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

				LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z489","Z480","Z098","Z549","Z479")
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
            v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(claim_items.other_price, 0) AS other_price,v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) AS claim_price,
            CASE 
                WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN 
                    IF((v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)) > 700, 700, (v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0)))
                WHEN lh.hmain_sss = "Y" THEN 370
                ELSE 120
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
                    GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                    SUM(op.sum_price) AS other_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn            
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ? 
            AND v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(claim_items.other_price,0) <> 0
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,
                  IFNULL(v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(op_data.other_price,0),0) AS claim_price,
                  CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL 
                       THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(op_data.other_price,0),0) 
                       ELSE 0 
                  END AS claim_sent_price,
                  stm.receive_total
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
                SELECT op.vn, 
                    SUM(CASE WHEN (li.ems = "Y" OR li.kidney = "Y") THEN op.sum_price ELSE 0 END) AS other_price
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
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
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(op_data.other_price, 0) AS other_price,
            v.income - IFNULL(rc.rcpt_money, 0) - COALESCE(op_data.other_price, 0) AS claim_price,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,MAX(ec.check_detail) AS check_detail
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
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
            LEFT JOIN (
                SELECT op.vn, 
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
            AND oe.moph_finance_upload_status IS NULL 
            AND fdh.seq IS NULL 
            AND ec.hn IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            COALESCE(op_data.other_price, 0) AS other_price,
            v.income - IFNULL(rc.rcpt_money, 0) - COALESCE(op_data.other_price, 0) AS claim_price,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,MAX(ec.check_detail) AS check_detail
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
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
            LEFT JOIN (
                SELECT op.vn, 
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
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND (oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs (Outprovince) ──────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code, li.kidney, li.ems,
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
                   p.name AS pttype, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   vp.confirm_and_locked
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
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

        // รายการเวชภัณฑ์/ค่าใช้จ่ายทุกรายการ (ดึงทั้งหมดไม่กรอง)
        $items = DB::connection('hosxp')->select('
            SELECT op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code, li.kidney, li.ems,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name,
                   COALESCE(d3.ref_code, d.sks_drug_code) AS tmtid
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN paidst pst ON pst.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

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
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
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
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $visits = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,
            claim_items.claim_list,
            COALESCE(claim_items.ppfs, 0) AS ppfs,v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,ec.status AS ec_status,
            IF(oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL, "Y", "N") AS is_sent
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
            LEFT JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS ppfs
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
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
                           li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
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
        foreach ($visits as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.stp_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'visits'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,stm.receive_total,
                  CASE WHEN oe.moph_finance_upload_status IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL OR stm.cid IS NOT NULL OR rep.vn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price
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
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
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
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $visits = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.refer, 0) AS refer,
            IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,ec.status AS ec_status,
            IF(oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL, "Y", "N") AS is_sent
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
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
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(CASE WHEN n.nhso_adp_code IN ("S1801","S1802") THEN op.sum_price ELSE 0 END) AS refer,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate
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
            AND p.hipdata_code = "STP" 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND COALESCE(op_data.is_kidney, 0) = 0 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_column($visits, 'seq');
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
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
        foreach ($visits as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
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
            FROM (SELECT o.vn,o.vstdate,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR csop.hn IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price
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
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ') 
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn  ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            IFNULL(v.paid_money, 0) AS paid_money,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            COALESCE(op_data.ems_price, 0) AS ems_price,
            v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(op_data.ems_price, 0) AS debtor,
            ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN s.`name` END) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS ems_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount, MAX(rid) AS rid
                FROM hrims.stm_ofc_csop 
                WHERE sys <> "HD" AND vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) csop ON csop.hn = pt.hn AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)      
            LEFT JOIN hrims.eclaim_status ec ON ec.seq = o.vn
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
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
            AND ec.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,IFNULL(v.paid_money, 0) AS paid_money,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            COALESCE(op_data.ems_price, 0) AS ems_price,
            v.income-IFNULL(rc.rcpt_money, 0)-COALESCE(op_data.ems_price, 0) AS debtor,
            IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total,
            stm_uc.receive_pp,IFNULL(stm.repno,csop.rid) AS repno,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN s.`name` END) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS ems_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
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
            LEFT JOIN hrims.eclaim_status ec ON ec.seq = o.vn
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR csop.hn IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y")',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validateOfc($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validateOfc($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.ofc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'search', 'claim', 'month', 'claim_price', 'claim_sent_price', 'receive_total'));
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
                   p.name AS pttype, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, IFNULL(v.paid_money,0) AS paid_money, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   vp.confirm_and_locked,
                   vp.request_funds,
                   IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
            LEFT JOIN ovst_seq oq ON oq.vn = o.vn
            LEFT JOIN (
                SELECT cid, vstdate, 
                       GROUP_CONCAT(DISTINCT approve_code ORDER BY approve_code SEPARATOR ",") AS edc_ktb,
                       GROUP_CONCAT(DISTINCT CONCAT(approve_code, " (", DATE_FORMAT(vsttime, "%H:%i"), ")") ORDER BY approve_code SEPARATOR ", ") AS edc_ktb_with_time
                FROM hrims.edc_approve_list
                GROUP BY cid, vstdate
            ) eal ON eal.cid = pt.cid AND eal.vstdate = o.vstdate
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
                   op.pttype, ptt.name AS pttype_name, n.nhso_adp_code AS nhso_adp_code
            FROM opitemrece op
            LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            LEFT JOIN paidst ps ON ps.paidst = op.paidst
            LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
            WHERE op.vn = ?', [$vn]);

        // Validate
        $validator = new \App\Services\ClaimValidator();
        $aspects = ['ppfs', 'endpoint'];
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

        return view('claim_op.ofc_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'search', 'claim', 'month', 'claim_price', 'receive_total'));
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price,
            IFNULL(stm.compensate_treatment,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
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
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
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
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            IFNULL(v.paid_money, 0) AS paid_money,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            0 AS ems_price
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
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
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc, eal.edc_ktb, eal.edc_ktb_with_time,
            o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,
            IFNULL(v.paid_money, 0) AS paid_money,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,stm.compensate_treatment AS receive_total,stm_uc.receive_pp,stm.repno,ec.status AS ec_status,
            pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds,
            0 AS ems_price
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
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
            AND (oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y")',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validateLgo($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validateLgo($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.lgo', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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

        return view('claim_op.lgo_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
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
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY")
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-IFNULL(rc.rcpt_money, 0) AS debtor,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5) 
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL 
            AND ec.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bkk 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y")',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.bkk', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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

        $search = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
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
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-IFNULL(rc.rcpt_money, 0) AS debtor,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL 
            AND ec.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_bmt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y")',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.bmt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bmt_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
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
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_bmt_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
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
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT"
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-IFNULL(rc.rcpt_money, 0) AS debtor,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL 
            AND ec.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn AS seq, pt.sex, v.age_y, vp.confirm_and_locked, vp.request_funds, IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income, IFNULL(v.paid_money, 0) AS paid_money, 0 AS ems_price,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_srt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "SRT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // ── Batch load claim items for all VNs ──────────────────────────────
        $allVns = array_merge(array_column($search, 'seq'), array_column($claim, 'seq'));
        $itemsByVn = [];
        if (!empty($allVns)) {
            $rawItems = DB::connection('hosxp')
                ->select('
                    SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                           li.ppfs, li.ems,
                           IFNULL(n.name, d.name) AS name
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                    AND (li.ppfs = "Y" OR li.ems = "Y")',
                $allVns);
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
        }

        // ── Run ClaimValidator on each row ──────────────────────────────────
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
        foreach ($claim as $row) {
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? [], ['ppfs', 'endpoint']);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }

        return view('claim_op.srt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) AS claim_price,
            CASE WHEN oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL THEN IFNULL(v.income-IFNULL(rc.rcpt_money, 0),0) ELSE 0 END AS claim_sent_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
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
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT"
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney LEFT JOIN nondrugitems n ON n.icode=kidney.icode WHERE kidney.vn=o.vn AND n.billcode = "71641")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-IFNULL(rc.rcpt_money, 0) AS debtor,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL 
            AND ec.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,IFNULL(rc.rcpt_money, 0) AS rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-IFNULL(rc.rcpt_money, 0) AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno,ec.status AS ec_status
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
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (
                SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_pvt 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY hn, vstdate, LEFT(vsttime,5)
            ) stm ON stm.hn = pt.hn AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (
                SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)
            ) stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "PVT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR ec.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.pvt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'search', 'claim'));
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
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
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
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
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
            rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status,MAX(ec.status) AS ec_status,
            pt.sex, v.age_y
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
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
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn  
                AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
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
                   vp.request_funds
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno=r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
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
                   li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code
            FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.ppfs = "Y"
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems d ON d.icode = op.icode
            WHERE op.vn = ?
            AND op.paidst = "02"', [$vn]);

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
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

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
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ? GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

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
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
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
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
                HAVING MAX(CASE WHEN n.billcode = "71641" THEN 1 ELSE 0 END) = 1
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            AND stm.cid IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

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
			WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
			WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

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

        $search = DB::connection('hosxp')->select('
            SELECT o.vstdate, o.vsttime, o.oqueue,o.vn, o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
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
            SELECT o.vstdate, o.vsttime, o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
                    AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn 
            ) AS a
            GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
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
            WHERE p.pttype IN (' . $pttype_act . ') AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

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
        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: "''";

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
                  CASE WHEN rep.vn IS NOT NULL THEN (v.income-IFNULL(rc.rcpt_money, 0)) ELSE 0 END AS claim_sent_price
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
            LEFT JOIN hrims.debtor_1102050101_301 d ON d.vn=o.vn
            LEFT JOIN (
                SELECT vn FROM hrims.sss_ssop_rep GROUP BY vn
            ) rep ON rep.vn = o.vn
            WHERE p.hipdata_code = "SSS"
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
                AND p.pttype NOT IN (' . $pttype_sss_fund . ')
                AND p.pttype NOT IN (' . $pttype_sss_ae . ')
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $claim_sent_price = array_column($sum_month, 'claim_sent_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vn,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            pt.cid, vp.begin_date, vp.expire_date,
            os.cc,
            MAX(CASE WHEN od.diagtype = "1" THEN od.icd10 END) AS pdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype NOT IN ("1", "2") THEN od.icd10 END) AS sdx,
            GROUP_CONCAT(DISTINCT CASE WHEN od.diagtype = "2" THEN od.icd10 END) AS icd9,
            v.income, v.uc_money, IFNULL(rc.rcpt_money, 0) AS rcpt_money, v.income-IFNULL(rc.rcpt_money, 0) AS claim_price,
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
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN hrims.debtor_1102050101_301 d ON d.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS"
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND p.pttype NOT IN (' . $pttype_sss_ae . ')
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        $ncd_json_path = base_path('docs/lookup/icd10_sss_chronic.json');
        $ncd_data = [];
        if (file_exists($ncd_json_path)) {
            $ncd_data = json_decode(file_get_contents($ncd_json_path), true);
        }
        $prefixes = $ncd_data['prefixes'] ?? [];
        $exclusions = $ncd_data['exclusions'] ?? [];

        $tmt_json_path = base_path('docs/lookup/tmt_sss_chronic.json');
        $tmt_diseases = [];
        if (file_exists($tmt_json_path)) {
            $tmt_data = json_decode(file_get_contents($tmt_json_path), true);
            $tmt_diseases = $tmt_data['diseases'] ?? [];
        }

        $vns = array_column($claim, 'vn');
        $drugs_by_vn = [];
        $rep_errors = [];
        $stm_pays = [];

        if (!empty($vns)) {
            $placeholders = implode(',', array_fill(0, count($vns), '?'));
            $drugs = DB::connection('hosxp')->select("
                SELECT op.vn, op.icode, sd.name, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                       gt.gpu_code, gg.gp_code, sd.sks_product_category_id, di.capacity_name, di.capacity_qty,
                       op.drugusage, op.qty
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

            // Fetch REP errors matching actual HOSxP vns
            $rep_errors = DB::table('sss_ssop_rep')
                ->whereIn('vn', $vns)
                ->pluck('error_codes', 'vn')
                ->toArray();

            // Fetch STM paid amount matching actual HOSxP vns
            $stm_pays = DB::table('sss_ssop_stm')
                ->whereIn('vn', $vns)
                ->pluck('total', 'vn')
                ->toArray();
        }

        foreach ($claim as $row) {
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
                    empty($drug->drugusage) || empty($drug->qty) || floatval($drug->qty) <= 0) {
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
            $row->stm_pay = $stm_pays[$row->vn] ?? null;

            // Determine eye status color: red (errors), yellow (warnings/not closed), green (all good & closed)
            $is_valid = (!empty($invoice_no) && $invoice_no !== '0' && $invoice_no !== '0.00' && $has_pdx && $has_claim_money && $has_valid_cid && $has_valid_hmain && $has_valid_dates);
            if (!$is_valid) {
                $row->claim_status = 'red';
            } elseif ($row->endpoint !== 'Y') {
                $row->claim_status = 'yellow';
            } else {
                $row->claim_status = 'green';
            }

        }

        return view('claim_op.sss_main', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'claim_sent_price', 'receive_total', 'claim'));
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
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN opdscreen os ON os.vn = o.vn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
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
            SELECT op.icode, sd.name, op.qty, op.sum_price, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
                   gt.gpu_code, gg.gp_code, op.drugusage,
                   CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text,
                   sd.sks_product_category_id, di.capacity_name, di.capacity_qty,
                   op.paidst AS paids, pst.name AS paids_name,
                   op.pttype, ptt.name AS pttype_name, ni.nhso_adp_code
            FROM opitemrece op
            INNER JOIN s_drugitems sd ON sd.icode = op.icode
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
            WHERE op.vn = ?
        ", [$vn]);

        $tmt_json_path = base_path('docs/lookup/tmt_sss_chronic.json');
        $tmt_diseases = [];
        if (file_exists($tmt_json_path)) {
            $tmt_data = json_decode(file_get_contents($tmt_json_path), true);
            $tmt_diseases = $tmt_data['diseases'] ?? [];
        }

        $ncd_json_path = base_path('docs/lookup/icd10_sss_chronic.json');
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

        // Fetch REP errors (Only from the latest imported REP file to avoid showing historical errors from old export runs)
        $latest_rep = DB::table('sss_ssop_rep')->where('vn', $vn)->orderBy('id', 'desc')->first();
        $rep_rows = [];
        if ($latest_rep) {
            $rep_rows = DB::table('sss_ssop_rep')
                ->where('vn', $vn)
                ->where('rep_file', $latest_rep->rep_file)
                ->get();
        }
        $dict_path = base_path('docs/lookup/sss_error_codes.json');
        $dict = [];
        if (file_exists($dict_path)) {
            $dict = json_decode(file_get_contents($dict_path), true);
        }

        $rep_feedbacks = [];
        $unique_feedbacks = []; // Avoid duplicate codes in display
        foreach ($rep_rows as $rr) {
            $codes = array_filter(array_map('trim', explode(',', $rr->error_codes)));
            foreach ($codes as $code) {
                $upCode = strtoupper($code);
                $key = $upCode;
                if (!isset($unique_feedbacks[$key])) {
                    $is_warning = str_starts_with($upCode, 'W');
                    $unique_feedbacks[$key] = [
                        'code' => $code,
                        'type' => $is_warning ? 'warning' : 'error',
                        'desc' => $dict[$upCode] ?? 'ไม่พบรายละเอียดข้อผิดพลาดในคู่มือ สกส.',
                        'file' => $rr->rep_file
                    ];
                }
            }
        }
        $rep_feedbacks = array_values($unique_feedbacks);

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
        $tmt_json_path = base_path('docs/lookup/tmt_sss_chronic.json');
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
        $ncd_json_path = base_path('docs/lookup/icd10_sss_chronic.json');
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


}
