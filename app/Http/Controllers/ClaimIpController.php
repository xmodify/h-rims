<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimIpController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_incup(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic (Optimized)
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (Optimized with AN filter in subquery)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an           
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ucs 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an  
                WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code IN ("UCS","WEL") 
                AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok ,
                fdh.status_message_th AS fdh_status
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL 
            AND fdh.an IS NULL
            AND stm.an IS NULL
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (Optimized)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
                fdh.status_message_th AS fdh_status
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")            
            AND (i.data_exp_date IS NOT NULL OR fdh.an IS NOT NULL OR stm.an IS NOT NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")))
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.ucs_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outcup(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (Out-CUP)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an           
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ucs 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an  
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code IN ("UCS","WEL") 
                AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (Out-CUP)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok ,
                fdh.status_message_th AS fdh_status
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL 
            AND fdh.an IS NULL
            AND stm.an IS NULL
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (Out-CUP)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
                fdh.status_message_th AS fdh_status
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")            
            AND (i.data_exp_date IS NOT NULL OR fdh.an IS NOT NULL OR stm.an IS NOT NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.ucs_outcup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function stp(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (STP)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ucs 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP"
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (STP)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND i.data_exp_date IS NULL
            AND stm.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (STP)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND (i.data_exp_date IS NOT NULL OR stm.an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.stp', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (OFC)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ofc 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) cipn ON cipn.an = i.an
                LEFT JOIN (
                    SELECT i2.an, SUM(c.amount) AS amount 
                    FROM hrims.stm_ofc_csop c
                    INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                    WHERE c.sys = "HD"
                    AND i2.confirm_discharge = "Y"
                    AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY i2.an
                ) csop ON csop.an = i.an
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code = "OFC"
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m',
            [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
        );

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (OFC)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC" 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            AND stm.an IS NULL AND cipn.an IS NULL AND csop.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (OFC)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0) AS receive_total,
                CONCAT_WS(",", stm.repno, cipn.rid, csop.rid) AS repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid 
                FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid 
                FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR cipn.an IS NOT NULL OR csop.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        return view('claim_ip.ofc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (LGO)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(compensate_treatment) AS receive_total 
                    FROM hrims.stm_lgo 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code = "LGO"
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (LGO)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an FROM hrims.stm_lgo 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" 
            AND stm.an IS NULL
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5")))
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (LGO)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                stm.case_iplg AS receive_treatment,stm.compensate_treatment AS receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an, SUM(case_iplg) AS case_iplg, SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.lgo', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bkk(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (BKK)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ofc 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) cipn ON cipn.an = i.an
                LEFT JOIN (
                    SELECT i2.an, SUM(c.amount) AS amount 
                    FROM hrims.stm_ofc_csop c
                    INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                    WHERE c.sys = "HD"
                    AND i2.confirm_discharge = "Y"
                    AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY i2.an
                ) csop ON csop.an = i.an
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code = "BKK"
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m',
            [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
        );

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (BKK)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            AND stm.an IS NULL AND cipn.an IS NULL AND csop.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (BKK)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0) AS receive_total,
                CONCAT_WS(",", stm.repno, cipn.rid, csop.rid) AS repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid 
                FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid 
                FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR cipn.an IS NOT NULL OR csop.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        return view('claim_ip.bkk', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bmt(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (BMT)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(receive_total) AS receive_total 
                    FROM hrims.stm_ofc 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) stm ON stm.an = i.an
                LEFT JOIN (
                    SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn 
                    WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                    GROUP BY an
                ) cipn ON cipn.an = i.an
                LEFT JOIN (
                    SELECT i2.an, SUM(c.amount) AS amount 
                    FROM hrims.stm_ofc_csop c
                    INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                    WHERE c.sys = "HD"
                    AND i2.confirm_discharge = "Y"
                    AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY i2.an
                ) csop ON csop.an = i.an
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code = "BMT"
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m',
            [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
        );

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (BMT)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            AND stm.an IS NULL AND cipn.an IS NULL AND csop.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (BMT)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0) AS receive_total,
                CONCAT_WS(",", stm.repno, cipn.rid, csop.rid) AS repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_ofc 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid 
                FROM hrims.stm_ofc_cipn 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) cipn ON cipn.an = i.an
            LEFT JOIN (
                SELECT i2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid 
                FROM hrims.stm_ofc_csop c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.vstdate BETWEEN i2.regdate AND i2.dchdate
                WHERE c.sys = "HD"
                AND i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) csop ON csop.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR cipn.an IS NOT NULL OR csop.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        return view('claim_ip.bmt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year & Date Range Logic
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year = $request->budget_year ?: $budget_year_now;
        $active_year = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);

        $start_date_b = $active_year->DATE_BEGIN ?? null;
        $end_date_b = $active_year->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        // 2. Query Sum Month (SSS)
        $sum_month = DB::connection('hosxp')->select('
            SELECT 
                month,
                COUNT(an) AS an,
                SUM(claim_price) AS claim_price,
                SUM(receive_total) AS receive_total
            FROM (
                SELECT 
                    CASE 
                        WHEN MONTH(i.dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(i.dchdate)+543, 2))
                        WHEN MONTH(i.dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(i.dchdate)+543, 2))
                    END AS month,
                    i.an,
                    (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                    (IFNULL(d.receive,0) + IFNULL(d1.receive,0) + IFNULL(d2.receive,0)) AS receive_total,
                    YEAR(i.dchdate) AS y, MONTH(i.dchdate) AS m
                FROM ipt i            
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype           
                LEFT JOIN (
                    SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype
                ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (
                    SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                    FROM rcpt_print r
                    INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                    WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                    GROUP BY r.vn
                ) rc ON rc.an = i.an
                LEFT JOIN hrims.debtor_1102050101_302 d ON d.an = i.an
                LEFT JOIN hrims.debtor_1102050101_304 d1 ON d1.an = i.an
                LEFT JOIN hrims.debtor_1102050101_308 d2 ON d2.an = i.an 
                WHERE i.confirm_discharge = "Y" 
                AND i.dchdate BETWEEN ? AND ?
                AND p.hipdata_code IN ("SSS","SSI")          
                GROUP BY i.an
            ) AS a
            GROUP BY y, m
            ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        // 3. Search Data (SSS)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (SSS)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.sss', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
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
            SELECT CASE WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                END AS month,COUNT(an) AS an,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
                        FROM (SELECT i.dchdate,i.an,COALESCE(hc_items.claim_price, 0) AS claim_price,d.receive AS receive_total
            FROM ipt i            
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype           
            INNER JOIN (
                SELECT op.an, SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN nondrugitems n ON op.icode = n.icode 
                INNER JOIN hrims.lookup_adp_sss a ON a.`code`=n.nhso_adp_code AND a.dateexp > DATE(NOW())
                INNER JOIN ipt i2 ON i2.an = op.an
                WHERE i2.dchdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.an
            ) hc_items ON hc_items.an = i.an
            LEFT JOIN hrims.debtor_1102050101_310 d ON d.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN  ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            GROUP BY i.an ) AS a
			GROUP BY YEAR(dchdate), MONTH(dchdate)
            ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            a.age_y, p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
            IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                        hc_items.claim_list,COALESCE(hc_items.claim_price, 0) AS claim_price
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward						
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
			LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an 
            INNER JOIN (
                SELECT op.an, 
                    GROUP_CONCAT(DISTINCT IFNULL(sd.`name`, n.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN nondrugitems n ON op.icode = n.icode 
                INNER JOIN hrims.lookup_adp_sss adp ON adp.`code`=n.nhso_adp_code AND adp.dateexp > DATE(NOW())
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                INNER JOIN ipt i4 ON i4.an = op.an
                WHERE i4.dchdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.an
            ) hc_items ON hc_items.an = i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN  ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.sss_hc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function gof(Request $request)
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
            SELECT CASE WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                END AS month,COUNT(an) AS an,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT i.dchdate,i.hn,i.an,(IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,d.receive AS receive_total
            FROM ipt i            
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype           
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN hrims.debtor_1102050102_109 d ON d.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("GOF","WVO")         
            GROUP BY i.an ) AS a
			GROUP BY YEAR(dchdate), MONTH(dchdate)
            ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("GOF","WVO") 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id            
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("GOF","WVO") 
            AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.gof', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
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

        $sum_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                END AS month,COUNT(an) AS an,SUM(IFNULL(claim_price,0)) AS claim_price
            FROM (SELECT i.dchdate,i.hn,i.an,
                (IFNULL(a.paid_money,0) - IFNULL(rc.rcpt_money,0)) AS claim_price
            FROM ipt i                                 
            LEFT JOIN an_stat a ON a.an=i.an           
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" 
            AND IFNULL(rc.rcpt_money,0) <> a.paid_money      
            GROUP BY i.an ) AS a
			GROUP BY YEAR(dchdate), MONTH(dchdate)
            ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b]);

        $sum_month_rcpt = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                END AS month,COUNT(an) AS an,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT i.dchdate,i.hn,i.an,IFNULL(rc.rcpt_money,0) AS receive_total
            FROM ipt i                                 
            LEFT JOIN an_stat a ON a.an=i.an           
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" 
            AND IFNULL(rc.rcpt_money,0) = a.paid_money      
            GROUP BY i.an ) AS a
			GROUP BY YEAR(dchdate), MONTH(dchdate)
            ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month_rcpt, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,
                IFNULL(inc.income,0) AS income, a.paid_money, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                a.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,
                r.rcpno,p2.arrear_date,p2.amount AS arrear_amount, r1.bill_amount AS paid_arrear,
                r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" AND IFNULL(rc.rcpt_money,0) <> a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,
                IFNULL(inc.income,0) AS income, a.paid_money, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                a.paid_money - IFNULL(rc.rcpt_money,0) AS claim_price,
                r.rcpno,p2.arrear_date,p2.amount AS arrear_amount, r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,
                fd.deposit_amount,fd1.debit_amount,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" AND IFNULL(rc.rcpt_money,0) = a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.rcpt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
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
            SELECT CASE WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(dchdate)+543, 2))
                WHEN MONTH(dchdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(dchdate)+543, 2))
                END AS month,COUNT(an) AS an,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT i.dchdate,i.hn,i.an,
                (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) AS claim_price,
                d.receive AS receive_total
            FROM ipt i            
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype           
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN hrims.debtor_1102050102_602 d ON d.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ')   
            GROUP BY i.an ) AS a
			GROUP BY YEAR(dchdate), MONTH(dchdate)
            ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);
        $month = array_column($sum_month, 'month');
        $claim_price = array_column($sum_month, 'claim_price');
        $receive_total = array_column($sum_month, 'receive_total');

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                i.adjrw,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ') 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS claim_price,
                i.adjrw,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (
                SELECT r.vn AS an,SUM(r.bill_amount) AS rcpt_money
                FROM rcpt_print r
                INNER JOIN ipt i3 ON i3.an = r.vn AND r.bill_date BETWEEN i3.regdate AND i3.dchdate
                WHERE NOT EXISTS (SELECT 1 FROM rcpt_abort a WHERE a.rcpno = r.rcpno)
                GROUP BY r.vn
            ) rc ON rc.an = i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ') 
            AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        return view('claim_ip.act', compact('budget_year_select', 'budget_year', 'start_date', 'end_date', 'month', 'claim_price', 'receive_total', 'search', 'claim'));
    }

}
