<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimOpController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_incup(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $start = microtime(true);
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,claim_items.total_price AS claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn            
            INNER JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS total_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
			AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
			AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        Log::info('ClaimOpController@ucs_incup sum_month: ' . (microtime(true) - $start) . 's');

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $start = microtime(true);
        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,claim_items.claim_list,
            v.income,v.rcpt_money,COALESCE(claim_items.claim_price, 0) AS claim_price,GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,
            fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN (li.kidney = "" OR li.kidney IS NULL) THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
                HAVING SUM(CASE WHEN (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y") THEN 1 ELSE 0 END) > 0
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND oe.moph_finance_upload_status IS NULL 
            AND rep.vn IS NULL 
            AND fdh.seq IS NULL
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_incup search: ' . (microtime(true) - $start) . 's');

        $start = microtime(true);
        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            o.vn AS seq,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,
            stm.receive_total,stm.repno,fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN li.uc_cr = "Y" THEN op.sum_price ELSE 0 END) AS uc_cr,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs,
                    SUM(CASE WHEN li.herb32 = "Y" THEN op.sum_price ELSE 0 END) AS herb
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_incup claim: ' . (microtime(true) - $start) . 's');

        return view('claim_op.ucs_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $start = microtime(true);
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,claim_items.claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn            
            INNER JOIN (
                SELECT op.vn, 
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
				AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code IN ("UCS","WEL")
			    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
                GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        Log::info('ClaimOpController@ucs_inprovince sum_month: ' . (microtime(true) - $start) . 's');

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $start = microtime(true);
        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,claim_items.claim_list,
            v.income,v.rcpt_money,COALESCE(claim_items.claim_price, 0) AS claim_price,GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,
            fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN (li.kidney = "" OR li.kidney IS NULL) THEN op.sum_price ELSE 0 END) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
                HAVING SUM(CASE WHEN (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y") THEN 1 ELSE 0 END) > 0
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code IN ("UCS","WEL")
                AND o.vstdate BETWEEN ? AND ?
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
                AND oe.moph_finance_upload_status IS NULL
                AND rep.vn IS NULL
                AND fdh.seq IS NULL
                AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_inprovince search: ' . (microtime(true) - $start) . 's');

        $start = microtime(true);
        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            o.vn AS seq,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            claim_items.claim_list,
            COALESCE(claim_items.uc_cr, 0) AS uc_cr,COALESCE(claim_items.ppfs, 0) AS ppfs,COALESCE(claim_items.herb, 0) AS herb,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,
            stm.receive_total,stm.repno,fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
                    SUM(CASE WHEN li.uc_cr = "Y" THEN op.sum_price ELSE 0 END) AS uc_cr,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs,
                    SUM(CASE WHEN li.herb32 = "Y" THEN op.sum_price ELSE 0 END) AS herb
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
                HAVING SUM(CASE WHEN (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y") THEN 1 ELSE 0 END) > 0
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code IN ("UCS","WEL")
                AND o.vstdate BETWEEN ? AND ?
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs IS NULL OR hmain_ucs =""))
                AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_inprovince claim: ' . (microtime(true) - $start) . 's');

        return view('claim_op.ucs_inprovince', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince_va(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $start = microtime(true);
        $sum = DB::connection('hosxp')->select('
            SELECT hospmain,COUNT(vn) AS visit,SUM(income) AS income,SUM(rcpt_money) AS rcpt_money,
            SUM(other_price) AS other_price,SUM(claim_price) AS claim_price,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN 1 ELSE 0 END) AS er_visit,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN claim_price ELSE 0 END) AS er_price,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN 1 ELSE 0 END) AS normal_visit,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN claim_price ELSE 0 END) AS normal_price
			FROM (SELECT v.vn,CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
			    CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"
				WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" END AS pt_status,						
				o.vstdate,o.vsttime,p.`name` AS pttype,v.pdx,v.income,v.rcpt_money,COALESCE(claim_items.other_price, 0) AS other_price,
				v.income-v.rcpt_money-COALESCE(claim_items.other_price,0) AS claim_price            
                FROM ovst o
				LEFT JOIN er_regist er ON er.vn=o.vn
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
				LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
                LEFT JOIN pttype p ON p.pttype=vp.pttype
                LEFT JOIN vn_stat v ON v.vn = o.vn
				LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z489","Z480","Z098","Z549","Z479")
                LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
					WHERE op.vstdate BETWEEN ? AND ?  GROUP BY op.vn) claim_items ON claim_items.vn=o.vn            
                WHERE (o.an ="" OR o.an IS NULL) 
                    AND p.hipdata_code IN ("UCS","WEL") 
                    AND o.vstdate BETWEEN ? AND ? 
                    AND v.income-v.rcpt_money-COALESCE(claim_items.other_price,0) <> 0
                    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
                    AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
                GROUP BY o.vn ORDER BY vp.hospmain,pt_status DESC,o.vstdate,o.vsttime) AS a	GROUP BY hospmain ORDER BY hospmain', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_inprovince_va sum: ' . (microtime(true) - $start) . 's');

        $start = microtime(true);
        $search = DB::connection('hosxp')->select('
            SELECT CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
            CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"			
			WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" 
            WHEN v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y" ) THEN "ส่งเสริมป้องกันโรคPP" 
			END AS pt_status,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
			p.`name` AS pttype,os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            COALESCE(claim_items.other_price, 0) AS other_price,v.income-v.rcpt_money-COALESCE(claim_items.other_price,0) AS claim_price,
            claim_items.other_list
            FROM ovst o
			LEFT JOIN er_regist er ON er.vn=o.vn
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
			LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
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
			AND v.income-v.rcpt_money-COALESCE(claim_items.other_price,0) <> 0
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
            GROUP BY o.vn ORDER BY vp.hospmain,pt_status DESC,o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_inprovince_va search: ' . (microtime(true) - $start) . 's');

        return view('claim_op.ucs_inprovince_va', compact('start_date', 'end_date', 'sum', 'search'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outprovince(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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

        $start = microtime(true);
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-v.rcpt_money,0) AS claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn            
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        Log::info('ClaimOpController@ucs_outprovince sum_month: ' . (microtime(true) - $start) . 's');
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $start = microtime(true);
        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,COALESCE(op_data.refer, 0) AS refer,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(CASE WHEN n.nhso_adp_code IN ("S1801","S1802") THEN op.sum_price ELSE 0 END) AS refer,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.moph_finance_upload_status IS NULL 
            AND rep.vn IS NULL 
            AND fdh.seq IS NULL 
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_outprovince search: ' . (microtime(true) - $start) . 's');

        $start = microtime(true);
        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,o.vn AS seq,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,COALESCE(op_data.refer, 0) AS refer,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno,
            fdh.status_message_th AS fdh_status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(CASE WHEN n.nhso_adp_code IN ("S1801","S1802") THEN op.sum_price ELSE 0 END) AS refer,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_outprovince claim: ' . (microtime(true) - $start) . 's');

        return view('claim_op.ucs_outprovince', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_kidney(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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

        $start = microtime(true);
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
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney = "Y"
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid 
				AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        Log::info('ClaimOpController@ucs_kidney sum_month: ' . (microtime(true) - $start) . 's');

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $start = microtime(true);
        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@ucs_kidney claim: ' . (microtime(true) - $start) . 's');

        return view('claim_op.ucs_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function stp_incup(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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

        $start = microtime(true);
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-v.rcpt_money AS claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")            
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,claim_items.claim_list,
            v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(d.`name`,n.`name`)) AS claim_list
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                LEFT JOIN nondrugitems n ON n.icode=op.icode
                LEFT JOIN drugitems d ON d.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) claim_items ON claim_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@stp_incup search: ' . (microtime(true) - $start) . 's');

        $start = microtime(true);
        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            claim_items.claim_list,
            COALESCE(claim_items.ppfs, 0) AS ppfs,v.income-v.rcpt_money AS claim_price,rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
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
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.stp_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function stp_outcup(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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

        $start = microtime(true);
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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-v.rcpt_money,0) AS claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,COALESCE(op_data.refer, 0) AS refer,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(CASE WHEN n.nhso_adp_code IN ("S1801","S1802") THEN op.sum_price ELSE 0 END) AS refer,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "STP" 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.moph_finance_upload_status IS NULL 
            AND rep.vn IS NULL 
            AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,COALESCE(op_data.refer, 0) AS refer,
            op_data.project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN (
                SELECT op.vn, 
                    SUM(CASE WHEN n.nhso_adp_code IN ("S1801","S1802") THEN op.sum_price ELSE 0 END) AS refer,
                    GROUP_CONCAT(DISTINCT CASE WHEN n.nhso_adp_code IN ("WALKIN","UCEP24") THEN n.nhso_adp_code END) AS project,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                LEFT JOIN hrims.lookup_icode li ON li.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "STP" 
            AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);
        Log::info('ClaimOpController@stp_outcup claim: ' . (microtime(true) - $start) . 's');

        return view('claim_op.stp_outcup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,IFNULL(v.income-v.rcpt_money,0) AS claim_price,
            IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total
            FROM ovst o        
			LEFT JOIN patient pt ON pt.hn=o.hn				
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype 
			LEFT JOIN vn_stat v ON v.vn = o.vn 	
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount
                FROM hrims.stm_ofc_csop WHERE sys <> "HD" GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)       
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ') 
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn  ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount, MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys <> "HD" GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)      
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            AND csop.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,IFNULL(stm.receive_total, 0) + IFNULL(csop.amount, 0) AS receive_total,
            stm_uc.receive_pp,IFNULL(stm.repno,csop.rid) AS repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys <> "HD" GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate AND csop.vsttime = LEFT(o.vsttime,5)  
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid=pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL OR csop.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.ofc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'search', 'claim', 'month', 'claim_price', 'receive_total'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc_kidney(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney = "Y"
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN (SELECT hn, vstdate, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys = "HD" GROUP BY hn, vstdate) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate 
            WHERE p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(csop.amount, 0) AS receive_total ,csop.rid AS repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, SUM(amount) AS amount,MAX(rid) AS rid
                FROM hrims.stm_ofc_csop WHERE sys = "HD" GROUP BY hn, vstdate) csop ON csop.hn = pt.hn
                AND csop.vstdate = o.vstdate 
            WHERE p.hipdata_code = "OFC" 
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.ofc_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'claim', 'month', 'claim_price', 'receive_total'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-v.rcpt_money,0) AS claim_price,
            IFNULL(stm.compensate_treatment,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo  GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid = pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo  GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.compensate_treatment AS receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo  GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid = pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "LGO" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.lgo', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo_kidney(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
            WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode=op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.lgo_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bkk(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-v.rcpt_money,0) AS claim_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid=pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY")
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid=pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("BKK","PTY") 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.bkk', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bmt(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,IFNULL(v.income-v.rcpt_money,0) AS claim_price,
            IFNULL(stm.receive_total,0)+IFNULL(stm_uc.receive_pp,0) AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype           
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid=pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND NOT EXISTS (SELECT 1 FROM opitemrece kidney INNER JOIN hrims.lookup_icode li ON li.icode=kidney.icode WHERE kidney.vn=o.vn AND li.kidney = "Y")
            GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.ppfs_list,v.income,
            v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND oe.upload_datetime IS NULL 
            AND stm.hn IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,op_data.ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(op_data.ppfs_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN (
                SELECT op.vn,
                    GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    MAX(CASE WHEN li.kidney = "Y" THEN 1 ELSE 0 END) AS is_kidney
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = pt.hn
                AND stm.vstdate = o.vstdate AND stm.vsttime = LEFT(o.vsttime,5)   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) stm_uc ON stm_uc.cid=pt.cid
                AND stm_uc.vstdate = o.vstdate AND stm_uc.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "BMT" 
            AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" 
            AND COALESCE(op_data.is_kidney, 0) = 0 
            AND (oe.upload_datetime IS NOT NULL OR stm.hn IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.bmt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_ppfs(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn, COALESCE(ppfs.claim_price, 0) AS claim_price,stm.receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype            
            LEFT JOIN vn_stat v ON v.vn = o.vn           
            INNER JOIN (
                SELECT op.vn, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) ppfs ON ppfs.vn = o.vn           
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) 
			AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") GROUP BY o.vn ) AS a
			GROUP BY YEAR(vstdate), MONTH(vstdate)
            ORDER BY YEAR(vstdate), MONTH(vstdate) ', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,
            op_data.claim_list,
            v.income,v.rcpt_money,COALESCE(op_data.claim_price, 0) AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(d.`name`, n.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND oe.upload_datetime IS NULL AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            op_data.claim_list,
            COALESCE(op_data.claim_price, 0) AS ppfs,oe.upload_datetime AS eclaim,rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(n.`name`, d.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs = "Y"
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) op_data ON op_data.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND (oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.sss_ppfs', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_fund(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-v.rcpt_money AS claim_price,d.receive AS receive_total
            FROM ovst o            
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN vn_stat v ON v.vn = o.vn
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
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE p.pttype IN (' . $pttype_sss_fund . ') 
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        return view('claim_op.sss_fund', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }

    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_kidney(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            kidney_items.claim_list,
            COALESCE(kidney_items.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn
            ) kidney_items ON kidney_items.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_op.sss_kidney', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_hc(Request $request)
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
                INNER JOIN nondrugitems n ON op.icode = n.icode 
                INNER JOIN hrims.lookup_adp_sss a ON a.`code`=n.nhso_adp_code AND a.dateexp > DATE(NOW())
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
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            hc_items.claim_list,
            COALESCE(hc_items.claim_price, 0) AS claim_price,d.receive AS receive_total,d.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn             
            INNER JOIN (
                SELECT op.vn, 
                    GROUP_CONCAT(DISTINCT IFNULL(sd.`name`, n.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN nondrugitems n ON op.icode = n.icode 
                INNER JOIN hrims.lookup_adp_sss a ON a.`code`=n.nhso_adp_code AND a.dateexp > DATE(NOW())
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
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
                    SELECT r.vn, SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    AND r.bill_date BETWEEN ? AND ?
                    GROUP BY r.vn
                ) rc ON rc.vn = o.vn
                WHERE (o.an IS NULL OR o.an = "")
                    AND o.vstdate BETWEEN ? AND ?
                    AND v.paid_money > 0
                GROUP BY o.vn
            ) AS a
            GROUP BY month
            ORDER BY MIN(vstdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_data, 'month');
        $claim_price = array_column($sum_data, 'claim_price');
        $receive_total = array_column($sum_data, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT o.vstdate, o.vsttime, o.oqueue,o.vn, o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income, v.paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,v.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,rc.rcpno,
                p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"รอยืนยันลูกหนี้" AS status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN patient_arrear p2 ON p2.vn = o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.bill_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno) 
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn           
            LEFT JOIN vn_stat v ON v.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND v.paid_money > 0
            AND v.paid_money - IFNULL(rc.rcpt_money,0) > 0
            AND o.vstdate BETWEEN ? AND ?
            ORDER BY o.vstdate, o.oqueue ', [$start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT o.vstdate, o.vsttime, o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
                os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.paid_money,
                v.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount
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
                SELECT r.vn, SUM(r.bill_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno) 
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
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $sum_month = DB::connection('hosxp')->select('
            SELECT month, COUNT(vn) AS visit, SUM(IFNULL(claim_price,0)) AS claim_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (
                SELECT o.vstdate, o.vn, v.income-v.rcpt_money AS claim_price, d.receive AS receive_total,
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
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price,
            d.receive AS receive_total
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hrims.debtor_1102050102_602 d ON d.vn=o.vn
            WHERE p.pttype IN (' . $pttype_act . ') AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        return view('claim_op.act', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'claim'));
    }

}
