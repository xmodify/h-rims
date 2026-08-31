<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimIpController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_claim_ip !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'เรียกเก็บ IP'], 403);
                }
                return $next($request);
            }
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_incup(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.ucs_incup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_ucs_incup_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN fdh_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL OR stm_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        fdh.an AS fdh_an,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
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
                        SELECT an, SUM(receive_total) AS receive_total 
                        FROM hrims.stm_ucs 
                        WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                        GROUP BY an
                    ) stm ON stm.an = i.an  
                    LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an
                        FROM hrims.rep_ucs
                        WHERE rep_type = "IP"
                        GROUP BY an
                    ) rep ON rep.an = i.an
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code IN ("UCS","WEL") 
                    AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_ucs
                WHERE rep_type = "IP"
                GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND fdh.an IS NULL
            AND ec.an IS NULL
            AND rep.an IS NULL
            AND stm.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (Optimized)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok,
                rep.error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")            
            AND (fdh.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->income = floatval($row->income);
            $row->rcpt_money = floatval($row->rcpt_money);
            $row->claim_price = $row->income - $row->rcpt_money;
            $row->is_valid = !empty($row->icd10) && $row->dch_sum === 'Y';
            $row->auth_valid = ($row->auth_code === 'Y');
        }
        foreach ($claim as $row) {
            $row->income = floatval($row->income);
            $row->rcpt_money = floatval($row->rcpt_money);
            $row->claim_price = $row->income - $row->rcpt_money;
            $row->is_valid = !empty($row->icd10) && $row->dch_sum === 'Y';
            $row->auth_valid = ($row->auth_code === 'Y');
        }

        
        $table_html = view('claim_ip.ucs_incup_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outcup(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.ucs_outcup', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_ucs_outcup_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN fdh_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL OR stm_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        fdh.an AS fdh_an,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
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
                        SELECT an, SUM(receive_total) AS receive_total 
                        FROM hrims.stm_ucs 
                        WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                        GROUP BY an
                    ) stm ON stm.an = i.an  
                    LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an
                        FROM hrims.rep_ucs
                        WHERE rep_type = "IP"
                        GROUP BY an
                    ) rep ON rep.an = i.an
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code IN ("UCS","WEL") 
                    AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m', [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]);

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Out-CUP)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_ucs
                WHERE rep_type = "IP"
                GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND fdh.an IS NULL
            AND ec.an IS NULL
            AND rep.an IS NULL
            AND stm.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (Out-CUP)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok,
                rep.error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") 
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")            
            AND (fdh.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL OR stm.an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->income = floatval($row->income);
            $row->rcpt_money = floatval($row->rcpt_money);
            $row->claim_price = $row->income - $row->rcpt_money;
            $row->is_valid = !empty($row->icd10) && $row->dch_sum === 'Y';
            $row->auth_valid = ($row->auth_code === 'Y');
        }
        foreach ($claim as $row) {
            $row->income = floatval($row->income);
            $row->rcpt_money = floatval($row->rcpt_money);
            $row->claim_price = $row->income - $row->rcpt_money;
            $row->is_valid = !empty($row->icd10) && $row->dch_sum === 'Y';
            $row->auth_valid = ($row->auth_code === 'Y');
        }

        
        $table_html = view('claim_ip.ucs_outcup_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function stp(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.stp', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_stp_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN fdh_an IS NOT NULL OR ec_an IS NOT NULL OR stm_an IS NOT NULL OR fdh_date IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        fdh.an AS fdh_an,
                        i.data_exp_date AS fdh_date,
                        ec.an AS ec_an,
                        stm.an AS stm_an,
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
                    LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
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

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Unclaimed Data ($search)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,pt.cid,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_ucs
                WHERE rep_type = "IP"
                GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND i.data_exp_date IS NULL
            AND fdh.an IS NULL
            AND ec.an IS NULL
            AND rep.an IS NULL
            AND stm.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data ($claim)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,pt.cid,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok,
                rep.error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an=i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an, MAX(fund_ip_payrate) AS fund_ip_payrate, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total, MAX(repno) AS repno FROM hrims.stm_ucs 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an  
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ucs
                    WHERE rep_type = "IP"
                ) ranked
                WHERE rn = 1
            ) rep ON rep.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND (i.data_exp_date IS NOT NULL OR fdh.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        $table_html = view('claim_ip.stp_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.ofc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_ofc_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select(
                '
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN ec_an IS NOT NULL OR stm_an IS NOT NULL OR cipn_an IS NOT NULL OR csop_an IS NOT NULL OR rep_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
                        cipn.an AS cipn_an,
                        csop.an AS csop_an,
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
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an 
                        FROM hrims.rep_ofc 
                        WHERE rep_type = "IP" AND dchdate BETWEEN ? AND ?
                        GROUP BY an
                    ) rep ON rep.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code = "OFC"
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m',
                [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
                );

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (OFC - Optimized)
        $search = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an 
                FROM hrims.rep_ofc 
                WHERE rep_type = "IP" AND dchdate BETWEEN ? AND ?
                GROUP BY an
            ) rep ON rep.an = i.an
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
            AND ec.an IS NULL
            AND stm.an IS NULL AND cipn.an IS NULL AND csop.an IS NULL AND rep.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (OFC - Optimized)
        $claim = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + IFNULL(csop.amount,0) AS receive_total,
                CONCAT_WS(",", stm.repno, cipn.rid, csop.rid, rep_eclaim.repno) AS repno,
                rep_eclaim.error_code AS rep_error_code, rep_eclaim.repno AS rep_repno,
                ec.check_detail AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_ofc
                    WHERE rep_type = "IP" AND dchdate BETWEEN ? AND ?
                ) t1 WHERE t1.rn = 1
            ) rep_eclaim ON rep_eclaim.an = i.an
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
            AND (ec.an IS NOT NULL OR stm.an IS NOT NULL OR cipn.an IS NOT NULL OR csop.an IS NOT NULL OR rep_eclaim.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
            if (!empty($row->repno)) {
                $row->repno = implode(',', array_unique(array_filter(explode(',', $row->repno))));
            }
        }

        
        $table_html = view('claim_ip.ofc_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.lgo', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_lgo_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN (ic_an IS NOT NULL AND ict_id IN ("4","5")) OR stm_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
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
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an FROM hrims.rep_lgo WHERE rep_type = "IP" GROUP BY an
                    ) rep ON rep.an = i.an
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

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_lgo WHERE rep_type = "IP" GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_lgo 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" 
            AND stm.an IS NULL AND ec.an IS NULL AND rep.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
  
        // 4. Claimed Data (LGO - Optimized)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                stm.case_iplg AS receive_treatment,stm.compensate_treatment AS receive_total,stm.repno,
                rep.error_code AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_lgo
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an, SUM(case_iplg) AS case_iplg, SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,"")) AS repno FROM hrims.stm_lgo 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" 
            AND (stm.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        
        $table_html = view('claim_ip.lgo_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bkk(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.bkk', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_bkk_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN (ic_an IS NOT NULL AND ict_id IN ("4","5")) OR stm_an IS NOT NULL OR kidney_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
                        kidney.an AS kidney_an,
                        (IFNULL(stm.receive_total,0) + IFNULL(kidney.receive_total,0)) AS receive_total,
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
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an FROM hrims.rep_bkk WHERE rep_type = "IP" GROUP BY an
                    ) rep ON rep.an = i.an
                    LEFT JOIN (
                        SELECT an, SUM(receive_total) AS receive_total 
                        FROM hrims.stm_bkk 
                        WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                        GROUP BY an
                    ) stm ON stm.an = i.an
                    LEFT JOIN (
                        SELECT i2.an, SUM(c.receive_total) AS receive_total, GROUP_CONCAT(c.repno) AS repno 
                        FROM hrims.stm_bkk_kidney c
                        INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                        WHERE i2.confirm_discharge = "Y"
                        AND i2.dchdate BETWEEN ? AND ?
                        GROUP BY i2.an
                    ) kidney ON kidney.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code = "BKK"
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m',
                [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
                );

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_bkk WHERE rep_type = "IP" GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_bkk 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT i2.an 
                FROM hrims.stm_bkk_kidney c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                WHERE i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" 
            AND stm.an IS NULL AND kidney.an IS NULL AND ec.an IS NULL AND rep.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (BKK - Optimized)
        $claim = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                (IFNULL(stm.receive_total,0) + IFNULL(kidney.receive_total,0)) AS receive_total,
                CONCAT_WS(",", stm.repno, kidney.repno) AS repno,
                rep.error_code AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_bkk
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_bkk 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT i2.an, SUM(c.receive_total) AS receive_total, GROUP_CONCAT(c.repno) AS repno 
                FROM hrims.stm_bkk_kidney c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                WHERE i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR kidney.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
            if (!empty($row->repno)) {
                $row->repno = implode(',', array_unique(array_filter(explode(',', $row->repno))));
            }
        }

        
        $table_html = view('claim_ip.bkk_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function bmt(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.bmt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_bmt_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select(
                '
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN (ic_an IS NOT NULL AND ict_id IN ("4","5")) OR stm_an IS NOT NULL OR kidney_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
                        kidney.an AS kidney_an,
                        (IFNULL(stm.receive_total,0) + IFNULL(kidney.receive_total,0)) AS receive_total,
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
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an FROM hrims.rep_bmt WHERE rep_type = "IP" GROUP BY an
                    ) rep ON rep.an = i.an
                    LEFT JOIN (
                        SELECT an, SUM(receive_total) AS receive_total 
                        FROM hrims.stm_bmt 
                        WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                        GROUP BY an
                    ) stm ON stm.an = i.an
                    LEFT JOIN (
                        SELECT i2.an, SUM(c.receive_total) AS receive_total 
                        FROM hrims.stm_bmt_kidney c
                        INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                        WHERE i2.confirm_discharge = "Y"
                        AND i2.dchdate BETWEEN ? AND ?
                        GROUP BY i2.an
                    ) kidney ON kidney.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code = "BMT"
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m',
                [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
                );

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_bmt WHERE rep_type = "IP" GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_bmt 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT i2.an 
                FROM hrims.stm_bmt_kidney c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                WHERE i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" 
            AND stm.an IS NULL AND kidney.an IS NULL AND ec.an IS NULL AND rep.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (BMT - Optimized)
        $claim = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                (IFNULL(stm.receive_total,0) + IFNULL(kidney.receive_total,0)) AS receive_total,
                CONCAT_WS(",", stm.repno, kidney.repno) AS repno,
                rep.error_code AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_bmt
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_bmt 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            LEFT JOIN (
                SELECT i2.an, SUM(c.receive_total) AS receive_total, GROUP_CONCAT(c.repno) AS repno 
                FROM hrims.stm_bmt_kidney c
                INNER JOIN ipt i2 ON i2.hn = c.hn AND c.datetimeadm BETWEEN i2.regdate AND i2.dchdate
                WHERE i2.confirm_discharge = "Y"
                AND i2.dchdate BETWEEN ? AND ?
                GROUP BY i2.an
            ) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR kidney.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
            if (!empty($row->repno)) {
                $row->repno = implode(',', array_unique(array_filter(explode(',', $row->repno))));
            }
        }

        
        $table_html = view('claim_ip.bmt_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function srt(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.srt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_srt_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select(
                '
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN (ic_an IS NOT NULL AND ict_id IN ("4","5")) OR stm_an IS NOT NULL OR ec_an IS NOT NULL OR rep_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        ec.an AS ec_an,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
                        IFNULL(stm.receive_total,0) AS receive_total,
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
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN (
                        SELECT an FROM hrims.rep_srt WHERE rep_type = "IP" GROUP BY an
                    ) rep ON rep.an = i.an
                    LEFT JOIN (
                        SELECT an, SUM(receive_total) AS receive_total 
                        FROM hrims.stm_srt 
                        WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                        GROUP BY an
                    ) stm ON stm.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code = "SRT"
                    GROUP BY i.an
                ) AS a
                GROUP BY y, m
                ORDER BY y, m',
                [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b]
                );

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (Wait for claim - Optimized)
        $search = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT an FROM hrims.rep_srt WHERE rep_type = "IP" GROUP BY an
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an FROM hrims.stm_srt 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "SRT" 
            AND stm.an IS NULL AND ec.an IS NULL AND rep.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        // 4. Claimed Data (SRT - Optimized)
        $claim = DB::connection('hosxp')->select(
            '
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                pt.cid, pt.sex,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
                IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                IFNULL(stm.receive_total,0) AS receive_treatment,
                IFNULL(stm.receive_total,0) AS receive_total,
                stm.repno AS repno,
                rep.error_code AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            LEFT JOIN (
                SELECT * FROM (
                    SELECT an, error_code, repno,
                           ROW_NUMBER() OVER (
                               PARTITION BY an 
                               ORDER BY 
                                   CASE WHEN error_code IS NULL OR error_code = "" THEN 1 ELSE 0 END DESC,
                                   repno DESC
                           ) AS rn
                    FROM hrims.rep_srt
                    WHERE rep_type = "IP"
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno 
                FROM hrims.stm_srt 
                WHERE an IN (SELECT an FROM ipt WHERE dchdate BETWEEN ? AND ? AND confirm_discharge = "Y")
                GROUP BY an
            ) stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "SRT" 
            AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) 
                OR stm.an IS NOT NULL OR ec.an IS NOT NULL OR rep.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
            if (!empty($row->repno)) {
                $row->repno = implode(',', array_unique(array_filter(explode(',', $row->repno))));
            }
        }

        
        $table_html = view('claim_ip.srt_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss(Request $request)
    {
        ini_set('max_execution_time', 0);

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

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.sss', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_sss_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN rep_an IS NOT NULL OR stm_an IS NOT NULL THEN (income - rcpt_money) ELSE 0 END) AS claim_sent_price,
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
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        rep.an AS rep_an,
                        stm.an AS stm_an,
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
                    LEFT JOIN hrims.rep_sss_aipn rep ON rep.an = i.an
                    LEFT JOIN hrims.stm_sss_aipn stm ON stm.an = i.an
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

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(COALESCE(NULLIF(ip.auth_code, \'\'), NULLIF(vp.auth_code, \'\')) <> "", "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                rep.error_codes AS rep_error_codes, rep.tcode AS rep_tcode, rep.repno AS rep_repno, rep.rep_date AS rep_rep_date, stm.receive_total AS stm_pay
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN visit_pttype vp ON vp.vn = i.vn
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT * FROM (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY an ORDER BY COALESCE(rep_date, \'1970-01-01\') DESC, COALESCE(rep_time, \'00:00:00\') DESC, COALESCE(repno, \'\') DESC, COALESCE(rep_file, \'\') DESC, id DESC) as rn
                    FROM hrims.rep_sss_aipn
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN hrims.stm_sss_aipn stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND rep.an IS NULL
            AND stm.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);
 
        // 4. Claimed Data (SSS - Optimized)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(COALESCE(NULLIF(ip.auth_code, \'\'), NULLIF(vp.auth_code, \'\')) <> "", "Y", NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                rep.error_codes AS rep_error_codes, rep.tcode AS rep_tcode, rep.repno AS rep_repno, rep.rep_date AS rep_rep_date, stm.receive_total AS stm_pay
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN visit_pttype vp ON vp.vn = i.vn
            LEFT JOIN ward w ON w.ward=i.vn
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (
                SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype
            ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT * FROM (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY an ORDER BY COALESCE(rep_date, \'1970-01-01\') DESC, COALESCE(rep_time, \'00:00:00\') DESC, COALESCE(repno, \'\') DESC, COALESCE(rep_file, \'\') DESC, id DESC) as rn
                    FROM hrims.rep_sss_aipn
                ) t1 WHERE t1.rn = 1
            ) rep ON rep.an = i.an
            LEFT JOIN hrims.stm_sss_aipn stm ON stm.an = i.an
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND (rep.an IS NOT NULL OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        // Process rep error codes and warnings for already submitted claims
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->rep_error = null;
            $row->rep_warning = null;
            if (!empty($row->rep_error_codes)) {
                $errs = [];
                $warns = [];
                $codes = explode(',', $row->rep_error_codes);
                foreach ($codes as $c) {
                    $parts = explode(':', $c, 2);
                    $base_code = trim($parts[0]);
                    $is_warn = str_starts_with(strtoupper($base_code), 'W') || str_starts_with($base_code, '8');
                    if ($is_warn) {
                        $warns[] = trim($c);
                    } else {
                        $errs[] = trim($c);
                    }
                }
                if (!empty($errs)) {
                    $row->rep_error = implode(',', $errs);
                }
                if (!empty($warns)) {
                    $row->rep_warning = implode(',', $warns);
                }
            }
        }

        // Structural validation for unsent admissions ($search)
        $validator = new \App\Services\ClaimValidator();

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->rep_error = null;
            $row->rep_warning = null;
            $errors = [];

            // 3. Coinsurance SSEM72 Check
            $pttypes = DB::connection('hosxp')->select("
                SELECT ip.pttype, p.hipdata_code, p.name, s.cipn_instype_code
                FROM ipt_pttype ip
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN sks_benefit_plan_type s ON s.sks_benefit_plan_type_id = p.sks_benefit_plan_type_id
                WHERE ip.an = ?
            ", [$row->an]);

            $has_ssem72 = false;
            foreach ($pttypes as $pt) {
                if ($pt->cipn_instype_code === 'SSEM72' || $pt->pttype === 'SSEM72' || strpos($pt->pttype, 'SSEM72') !== false || strpos($pt->name, 'SSEM72') !== false) {
                    $has_ssem72 = true;
                    break;
                }
            }

            $payplan = DB::connection('hosxp')->table('ipt as i')
                ->leftJoin('ipt_pttype as ip', 'ip.an', '=', 'i.an')
                ->leftJoin('pttype as p', 'p.pttype', '=', 'ip.pttype')
                ->leftJoin('pttype_upp_type as pu', 'pu.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
                ->where('i.an', $row->an)
                ->value('pu.pttype_upp_type_code') ?: '80';

            if (in_array($payplan, ['85', '95']) && !$has_ssem72) {
                $errors[] = "ขาดสิทธิ Coinsurance SSEM72";
            }

            // 4. ICD10 Check
            if (!empty($row->icd10)) {
                $val_res = $validator->validateIcd10Chi($row->icd10, '1');
                if (!$val_res['is_valid'] && !in_array(substr($row->icd10, 0, 2), ['U5', 'U6', 'U7'])) {
                    $errors[] = "รหัสวินิจฉัยหลัก {$row->icd10} ไม่ถูกต้องตาม CHI";
                }
            } else {
                $errors[] = "ไม่มีรหัสวินิจฉัยหลัก (PDX)";
            }

            // 5. Lab/Blood Catalog (06, 07) Check
            $opd_items = DB::connection('hosxp')->select("
                SELECT o.icode, o.income, o.qty, o.sum_price, o.unitprice
                FROM opitemrece o
                WHERE o.an = ? AND o.income IN ('06', '07')
            ", [$row->an]);
            
            if (!empty($opd_items)) {
                foreach ($opd_items as $item) {
                    $qty = (float)$item->qty;
                    $unitprice = (float)$item->unitprice;
                    $charge_amt = (float)$item->sum_price ?: ($qty * $unitprice);
                    if ($charge_amt <= 0 || $qty <= 0) {
                        continue;
                    }
                    $lab = DB::table('labcat_chi')
                        ->where('lccode', $item->icode)
                        ->orWhere('cscode', $item->icode)
                        ->first();
                    if (!$lab) {
                        $errors[] = "รหัสบริการ {$item->icode} ไม่อยู่ใน Lab Catalog";
                    } else {
                        if (empty($lab->tmlt)) {
                            $errors[] = "รหัสบริการ {$item->icode} ไม่มีรหัส TMLT/STDCode (Error 644)";
                        }
                    }
                }
            }

            // 6. Drug Catalog Check
            $opd_drugs = DB::connection('hosxp')->select("
                SELECT o.icode, o.income, o.qty, o.sum_price, o.unitprice
                FROM opitemrece o
                WHERE o.an = ? AND o.income IN ('03', '04')
            ", [$row->an]);
            
            if (!empty($opd_drugs)) {
                foreach ($opd_drugs as $item) {
                    $qty = (float)$item->qty;
                    $unitprice = (float)$item->unitprice;
                    $charge_amt = (float)$item->sum_price ?: ($qty * $unitprice);
                    if ($charge_amt <= 0 || $qty <= 0) {
                        continue;
                    }
                    $drug = DB::table('drugcat_chi')
                        ->where('hospdrugcode', $item->icode)
                        ->first();
                    if (!$drug) {
                        $errors[] = "รหัสยา {$item->icode} ไม่อยู่ใน Drug Catalog";
                    } else {
                        if (empty($drug->tmtid)) {
                            if ((int)$drug->productcat < 3) {
                                $errors[] = "รหัสยา {$item->icode} ไม่มีรหัส TMTID/STDCode (Error 644)";
                            }
                        }
                    }
                }
            }

            // Check Operation dates (Error 251)
            $procs = DB::connection('hosxp')->select("
                SELECT opdate, icd9
                FROM iptoprt
                WHERE an = ?
            ", [$row->an]);
            foreach ($procs as $p) {
                if (!empty($p->opdate)) {
                    if ($p->opdate < $row->regdate || $p->opdate > $row->dchdate) {
                        $errors[] = "วันทำหัตถการ {$p->opdate} ({$p->icd9}) ออกช่วงการรักษา (Error 251)";
                    }
                }
            }

            if (!empty($errors)) {
                $row->rep_error = implode(', ', $errors);
            }
        }

        $ans = array_column($claim, 'an');
        $passed_a_ans = [];
        if (!empty($ans)) {
            $passed_a_ans = DB::table('rep_sss_aipn')
                ->whereIn('an', $ans)
                ->where('tcode', 'A')
                ->distinct()
                ->pluck('an')
                ->toArray();
        }
        $passed_a_map = array_flip($passed_a_ans);

        // Pre-audit validation loop for already sent/denied claims to check current status in HOSxP
        foreach ($claim as $row) {
            $row->current_errors = null;
            $c_errors = [];

            // 1. Coinsurance SSEM72 Check
            $pttypes = DB::connection('hosxp')->select("
                SELECT ip.pttype, p.name, s.cipn_instype_code
                FROM ipt_pttype ip
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN sks_benefit_plan_type s ON s.sks_benefit_plan_type_id = p.sks_benefit_plan_type_id
                WHERE ip.an = ?
            ", [$row->an]);

            $has_ssem72 = false;
            foreach ($pttypes as $pt) {
                if ($pt->cipn_instype_code === 'SSEM72' || $pt->pttype === 'SSEM72' || strpos($pt->pttype, 'SSEM72') !== false || strpos($pt->name, 'SSEM72') !== false) {
                    $has_ssem72 = true;
                    break;
                }
            }

            $payplan = DB::connection('hosxp')->table('ipt as i')
                ->leftJoin('ipt_pttype as ip', 'ip.an', '=', 'i.an')
                ->leftJoin('pttype as p', 'p.pttype', '=', 'ip.pttype')
                ->leftJoin('pttype_upp_type as pu', 'pu.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
                ->where('i.an', $row->an)
                ->value('pu.pttype_upp_type_code') ?: '80';

            if (in_array($payplan, ['85', '95']) && !$has_ssem72) {
                $c_errors[] = "ขาดสิทธิ Coinsurance SSEM72";
            }

            // 2. PDX Check
            if (!empty($row->icd10)) {
                $val_res = $validator->validateIcd10Chi($row->icd10, '1');
                if (!$val_res['is_valid'] && !in_array(substr($row->icd10, 0, 2), ['U5', 'U6', 'U7'])) {
                    $c_errors[] = "รหัสวินิจฉัยหลัก {$row->icd10} ไม่ถูกต้องตาม CHI";
                }
            } else {
                $c_errors[] = "ไม่มีรหัสวินิจฉัยหลัก (PDX)";
            }

            // 3. Lab Catalog Check
            $opd_items = DB::connection('hosxp')->select("
                SELECT o.icode, o.income
                FROM opitemrece o
                WHERE o.an = ? AND o.income IN ('06', '07')
            ", [$row->an]);
            if (!empty($opd_items)) {
                foreach ($opd_items as $item) {
                    $lab = DB::table('labcat_chi')
                        ->where('lccode', $item->icode)
                        ->orWhere('cscode', $item->icode)
                        ->first();
                    if (!$lab) {
                        $c_errors[] = "รหัสบริการ {$item->icode} ไม่อยู่ใน Lab Catalog";
                    } else {
                        if (empty($lab->tmlt)) {
                            $c_errors[] = "รหัสบริการ {$item->icode} ไม่มีรหัส TMLT/STDCode (Error 644)";
                        }
                    }
                }
            }

            // 4. Drug Catalog Check
            $opd_drugs = DB::connection('hosxp')->select("
                SELECT o.icode, o.income
                FROM opitemrece o
                WHERE o.an = ? AND o.income IN ('03', '04')
            ", [$row->an]);
            if (!empty($opd_drugs)) {
                foreach ($opd_drugs as $item) {
                    $drug = DB::table('drugcat_chi')
                        ->where('hospdrugcode', $item->icode)
                        ->first();
                    if (!$drug) {
                        $c_errors[] = "รหัสยา {$item->icode} ไม่อยู่ใน Drug Catalog";
                    } else {
                        if (empty($drug->tmtid)) {
                            $c_errors[] = "รหัสยา {$item->icode} ไม่มีรหัส TMTID/STDCode (Error 644)";
                        }
                    }
                }
            }

            // Check Operation dates (Error 251)
            $procs = DB::connection('hosxp')->select("
                SELECT opdate, icd9
                FROM iptoprt
                WHERE an = ?
            ", [$row->an]);
            foreach ($procs as $p) {
                if (!empty($p->opdate)) {
                    if ($p->opdate < $row->regdate || $p->opdate > $row->dchdate) {
                        $c_errors[] = "วันทำหัตถการ {$p->opdate} ({$p->icd9}) ออกช่วงการรักษา (Error 251)";
                    }
                }
            }

            if (!empty($c_errors)) {
                $row->current_errors = implode(', ', $c_errors);
            }
        }

        $warning = [];
        $claim_sent = [];
        foreach ($claim as $row) {
            $ever_passed_a = isset($passed_a_map[$row->an]);
            if (($row->rep_tcode ?? null) === 'C' && $row->stm_pay === null && !$ever_passed_a) {
                $warning[] = $row;
            } else {
                $claim_sent[] = $row;
            }
        }
        $claim = $claim_sent;
        
        $table_html = view('claim_ip.sss_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim', 'warning'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_detail(Request $request)
    {
        $an = $request->an;
        if (empty($an)) {
            return response()->json(['error' => 'Invalid AN'], 400);
        }

        $rep_feedbacks = [];
        $rep_record = DB::table('rep_sss_aipn')
            ->where('an', $an)
            ->orderByDesc('rep_date')
            ->orderByDesc('rep_time')
            ->orderByDesc('id')
            ->first();
        if ($rep_record && !empty($rep_record->error_codes)) {
            $codes = array_filter(array_map('trim', explode(',', $rep_record->error_codes)));
            $lookup = [];
            $json_path = base_path('docs/lookup/sss_error_codes.json');
            if (file_exists($json_path)) {
                $lookup = json_decode(file_get_contents($json_path), true) ?: [];
            }
            foreach ($codes as $c) {
                $parts = explode(':', $c, 2);
                $base_code = trim($parts[0]);

                $desc = $lookup[$base_code] ?? $lookup[$c] ?? 'ไม่พบข้อมูลในคู่มือ';
                $is_warn = str_starts_with(strtoupper($base_code), 'W') || str_starts_with($base_code, '8') || (is_numeric($base_code) && (int)$base_code >= 800);
                $rep_feedbacks[] = [
                    'code' => $c,
                    'type' => $is_warn ? 'warning' : 'error',
                    'desc' => $desc
                ];
            }
        }

        return response()->json([
            'success' => true,
            'rep_feedbacks' => $rep_feedbacks
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_rep_errors(Request $request)
    {
        $errors = DB::connection('hosxp')->select('
            SELECT rep.an, rep.hn, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, 
                   rep.rep_file, rep.repno, rep.rep_date, rep.rep_time, rep.error_codes, rep.tcode
            FROM (
                SELECT * FROM (
                    SELECT *, ROW_NUMBER() OVER (PARTITION BY an ORDER BY COALESCE(rep_date, \'1970-01-01\') DESC, COALESCE(rep_time, \'00:00:00\') DESC, COALESCE(repno, \'\') DESC, COALESCE(rep_file, \'\') DESC, id DESC) as rn
                    FROM hrims.rep_sss_aipn
                ) t1 WHERE t1.rn = 1
            ) rep
            LEFT JOIN patient pt ON pt.hn = rep.hn
            LEFT JOIN hrims.stm_sss_aipn stm ON stm.an = rep.an
            WHERE rep.error_codes IS NOT NULL 
              AND rep.error_codes <> ""
              AND stm.an IS NULL
            ORDER BY rep.id DESC
            LIMIT 500
        ');

        return response()->json([
            'success' => true,
            'data' => $errors
        ]);
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
        $end_date_b = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.sss_hc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_sss_hc_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
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
                    INNER JOIN hrims.lookup_sss_equipdev_aipn a ON a.`code`=n.nhso_adp_code AND a.dateexp > DATE(NOW())
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

                $m = array_column($sum_month, 'month');
                return [
                    'month' => $m,
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_fill(0, count($m), 0),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (SSS_HC - Optimized)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            a.age_y, p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,
            IFNULL(inc.income,0) AS income, 
            (SELECT IFNULL(SUM(r.total_amount), 0)
             FROM rcpt_print r 
             LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
             WHERE r.vn = i.an AND a.rcpno IS NULL
            ) AS rcpt_money,
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
			LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an 
            INNER JOIN (
                SELECT op.an, 
                    GROUP_CONCAT(DISTINCT IFNULL(sd.`name`, n.`name`)) AS claim_list,
                    SUM(op.sum_price) AS claim_price
                FROM opitemrece op
                INNER JOIN nondrugitems n ON op.icode = n.icode 
                INNER JOIN hrims.lookup_sss_equipdev_aipn adp ON adp.`code`=n.nhso_adp_code AND adp.dateexp > DATE(NOW())
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                INNER JOIN ipt i4 ON i4.an = op.an
                WHERE i4.dchdate BETWEEN ? AND ?
                AND op.paidst = "02"
                GROUP BY op.an
            ) hc_items ON hc_items.an = i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN  ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        
        $table_html = view('claim_ip.sss_hc_table', compact('budget_year', 'start_date', 'end_date', 'search'))->render();

        $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search);

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function gof(Request $request)
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
        $end_date_b = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.gof', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_gof_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN (ic_an IS NOT NULL AND ict_id IN ("4","5")) OR ec_an IS NOT NULL
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        i.dchdate,
                        i.an,
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        ec.an AS ec_an,
                        IFNULL(d.receive,0) AS receive_total
                    FROM ipt i            
                    LEFT JOIN ipt_pttype ip ON ip.an = i.an
                    LEFT JOIN pttype p ON p.pttype = ip.pttype           
                    LEFT JOIN (
                        SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                        FROM opitemrece o
                        INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                        GROUP BY o.an, o.pttype
                    ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
                    LEFT JOIN hrims.debtor_1102050102_109 d ON d.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.hipdata_code IN ("BFC","GOF","WVO")         
                    GROUP BY i.an
                ) AS a
                GROUP BY YEAR(dchdate), MONTH(dchdate)
                ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        // 3. Search Data (GOF - Optimized)
        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BFC","GOF","WVO") 
            AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            AND ec.an IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        // 4. Claimed Data (GOF - Optimized)
        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                CONCAT(r.refer_hospcode, IF(ia.ac_ae = "Y", "[ucae=Y]", "")) AS refer,i.adjrw,ict.ipt_coll_status_type_name,
                ec.check_detail AS rep_error,
                ec.status AS ec_status
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
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id            
            LEFT JOIN hrims.eclaim_status ec ON ec.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BFC","GOF","WVO") 
            AND (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5") OR ec.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        $table_html = view('claim_ip.gof_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
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
        $end_date_b = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.rcpt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_rcpt_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    CASE 
                        WHEN MONTH(dchdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(dchdate)+543, 2))
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
                    END AS month,
                    SUM(CASE WHEN rcpt_money <> paid_money THEN (paid_money - rcpt_money) ELSE 0 END) AS claim_price,
                    SUM(CASE WHEN rcpt_money = paid_money THEN rcpt_money ELSE 0 END) AS receive_total
                FROM (
                    SELECT i.dchdate, i.an, IFNULL(a.paid_money,0) AS paid_money, 
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money
                    FROM ipt i                                 
                    LEFT JOIN an_stat a ON a.an=i.an           
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND a.paid_money <> "0"
                    GROUP BY i.an
                ) AS a
                GROUP BY YEAR(dchdate), MONTH(dchdate)
                ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b]);

                $m = array_column($sum_month, 'month');
                return [
                    'month' => $m,
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_fill(0, count($m), 0),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,
                IFNULL(inc.income,0) AS income, a.paid_money, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                (SELECT GROUP_CONCAT(r.rcpno ORDER BY r.rcpno)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpno,
                p2.arrear_date,p2.amount AS arrear_amount, r1.total_amount AS paid_arrear,
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
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" 
            AND (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) <> a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype', [$start_date, $end_date, $start_date, $end_date]);

        $claim = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,
                IFNULL(inc.income,0) AS income, a.paid_money, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                (SELECT GROUP_CONCAT(r.rcpno ORDER BY r.rcpno)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpno,
                p2.arrear_date,p2.amount AS arrear_amount, r1.total_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,
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
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <> "0" 
            AND (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) = a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        
        $table_html = view('claim_ip.rcpt_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }

    //----------------------------------------------------------------------------------------------------------------------------------------
    public function act(Request $request)
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
        $end_date_b = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('claim_ip.act', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $chartCacheKey = 'chart_ip_act_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
            $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b, $pttype_act) {
                $sum_month = DB::connection('hosxp')->select('
                SELECT 
                    month,
                    COUNT(an) AS an,
                    SUM(income - rcpt_money) AS claim_price,
                    SUM(CASE WHEN ic_an IS NOT NULL AND ict_id IN ("4","5")
                             THEN (income - rcpt_money)
                             ELSE 0 
                        END) AS claim_sent_price,
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
                        i.dchdate,
                        i.an,
                        IFNULL(inc.income,0) AS income,
                        (SELECT IFNULL(SUM(r.total_amount), 0)
                         FROM rcpt_print r 
                         LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                         WHERE r.vn = i.an AND a.rcpno IS NULL
                        ) AS rcpt_money,
                        ic.an AS ic_an,
                        ict.ipt_coll_status_type_id AS ict_id,
                        IFNULL(d.receive,0) AS receive_total
                    FROM ipt i            
                    LEFT JOIN ipt_pttype ip ON ip.an = i.an
                    LEFT JOIN pttype p ON p.pttype = ip.pttype           
                    LEFT JOIN (
                        SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                        FROM opitemrece o
                        INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                        GROUP BY o.an, o.pttype
                    ) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                    LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
                    LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
                    LEFT JOIN hrims.debtor_1102050102_602 d ON d.an = i.an
                    WHERE i.confirm_discharge = "Y" 
                    AND i.dchdate BETWEEN ? AND ?
                    AND p.pttype IN (' . $pttype_act . ')   
                    GROUP BY i.an 
                ) AS a
                GROUP BY YEAR(dchdate), MONTH(dchdate)
                ORDER BY YEAR(dchdate), MONTH(dchdate)', [$start_date_b, $end_date_b, $start_date_b, $end_date_b]);

                return [
                    'month' => array_column($sum_month, 'month'),
                    'claim_price' => array_column($sum_month, 'claim_price'),
                    'claim_sent_price' => array_column($sum_month, 'claim_sent_price'),
                    'receive_total' => array_column($sum_month, 'receive_total'),
                ];
            });

            $month = $chartData['month'] ?? [];
            $claim_price = $chartData['claim_price'] ?? [];
            $claim_sent_price = $chartData['claim_sent_price'] ?? [];
            $receive_total = $chartData['receive_total'] ?? [];
        }

        $search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
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
            SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,
                IFNULL(inc.income,0) AS income, 
                (SELECT IFNULL(SUM(r.total_amount), 0)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpt_money,
                0 AS claim_price,
                (SELECT GROUP_CONCAT(r.rcpno ORDER BY r.rcpno)
                 FROM rcpt_print r 
                 LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                 WHERE r.vn = i.an AND a.rcpno IS NULL
                ) AS rcpno,
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
            LEFT JOIN iptdiag id ON id.an=i.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ') 
            AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($search as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }
        foreach ($claim as $row) {
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
            $row->is_valid = !empty($row->cid) && strlen(trim($row->cid)) === 13 && !empty($row->hn) && !empty($row->icd10) && !empty($row->regdate) && !empty($row->dchdate);
            $row->auth_valid = !empty($row->auth_code) && $row->auth_code === 'Y';
        }

        $table_html = view('claim_ip.act_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();

        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => '', 'an' => $row->an], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => [
                'month' => $month ?? [],
                'claim_price' => $claim_price ?? [],
                'claim_sent_price' => $claim_sent_price ?? [],
                'receive_total' => $receive_total ?? []
            ]
        ]);
    }

    /**
     * ดึงข้อมูลรายละเอียดการรับบริการผู้ป่วยใน (IPD) และตรวจสอบความพร้อม 16 แฟ้ม สำหรับ Modal
     */
    public function get_ip_visit_details(Request $request)
    {
        $an = $request->input('an') ?? $request->input('vn');
        if (empty($an)) {
            return response()->json(['error' => 'กรุณาระบุ AN'], 400);
        }

        // ดึงข้อมูลหลักของการ Admit จาก ipt, an_stat, patient
        $visit = DB::connection('hosxp')->selectOne('
            SELECT i.an, i.hn, i.regdate, i.regtime, i.dchdate, i.dchtime,
                   i.dchstts, ds.name AS dchstts_name,
                   i.dchtype, dt.name AS dchtype_name,
                   i.ward, w.name AS ward_name,
                   i.bw AS adm_w, i.adjrw, i.drg, i.wtlos, i.ot, i.data_ok, i.data_exp_date,
                   IF(id.an <> "","Y",NULL) AS dch_sum,
                   IF((SELECT COUNT(*) FROM ipt_doctor_diag idd WHERE idd.an = i.an AND (idd.audit_ok = "Y" OR (idd.audit_diag_text IS NOT NULL AND idd.audit_diag_text <> "") OR (idd.audit_doctor_code IS NOT NULL AND idd.audit_doctor_code <> ""))) > 0, "Y", "N") AS audit_status,
                   (SELECT GROUP_CONCAT(DISTINCT doc_a.name SEPARATOR ", ") FROM ipt_doctor_diag idd LEFT JOIN doctor doc_a ON doc_a.code = idd.audit_doctor_code WHERE idd.an = i.an AND (idd.audit_ok = "Y" OR (idd.audit_diag_text IS NOT NULL AND idd.audit_diag_text <> ""))) AS audit_doctor_name,
                   (SELECT MAX(idd.audit_datetime) FROM ipt_doctor_diag idd WHERE idd.an = i.an AND (idd.audit_ok = "Y" OR (idd.audit_diag_text IS NOT NULL AND idd.audit_diag_text <> ""))) AS audit_datetime,
                   ict.ipt_coll_status_type_name AS coll_status,
                   pt.cid, pt.sex, a.age_y, pt.birthday,
                   CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,
                   p.name AS pttype, p.hipdata_code, ip.hospmain,
                   a.income, a.uc_money, a.paid_money,
                   IFNULL(rc.rcpt_money, 0) AS rcpt_money,
                   IF((ip.auth_code IS NOT NULL AND ip.auth_code <> ""), "Y", NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status = "success" OR ip.claim_code LIKE "EP%"), "Y", NULL) AS endpoint,
                   ep.claim_status,
                   fdh.status_message_th AS fdh_status,
                   ec.status AS ec_status,
                   ec.check_detail AS rep_error,
                   id.icd10 AS pdx,
                   i10.name AS pdx_name,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license,
                   dch_doc.name AS dch_doctor_name, dch_doc.licenseno AS dch_doctor_license
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN dchstts ds ON ds.dchstts = i.dchstts
            LEFT JOIN dchtype dt ON dt.dchtype = i.dchtype
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
            LEFT JOIN icd101 i10 ON i10.code = id.icd10
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money 
                FROM rcpt_print r 
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn
            ) rc ON rc.vn = i.an
            LEFT JOIN (
                SELECT cid, vstdate,
                       MAX(CASE WHEN claimCode LIKE "EP%" OR claim_status = "success" THEN claimCode END) AS claimCode,
                       MAX(CASE WHEN claimCode LIKE "EP%" OR claim_status = "success" THEN "success" ELSE claim_status END) AS claim_status
                FROM hrims.nhso_endpoint
                GROUP BY cid, vstdate
            ) ep ON ep.cid = pt.cid AND ep.vstdate = i.dchdate
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.an = i.an
            LEFT JOIN hrims.eclaim_status ec ON ec.an = i.an
            LEFT JOIN doctor doc ON doc.code = i.admdoctor
            LEFT JOIN doctor dch_doc ON dch_doc.code = i.dch_doctor
            WHERE i.an = ?', [$an]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลการรับบริการ AN: ' . $an], 404);
        }

        // คำนวณจำนวนวันนอน (LOS)
        if (!empty($visit->regdate) && !empty($visit->dchdate)) {
            $diff = (strtotime($visit->dchdate) - strtotime($visit->regdate)) / 86400;
            $visit->los = max(1, (int)$diff);
        } else {
            $visit->los = 1;
        }

        // แปลงน้ำหนักแรกรับเป็น kg (ถ้าเป็นกรัม > 500 ให้หาร 1000)
        if (!empty($visit->adm_w)) {
            $rawBw = floatval($visit->adm_w);
            $visit->adm_w = $rawBw > 500 ? number_format($rawBw / 1000, 1) : number_format($rawBw, 1);
        }

        // โรครอง (Secondary Diagnoses)
        $secDiags = DB::connection('hosxp')->select('
            SELECT id.icd10, COALESCE(i10.name, "") AS name, id.diagtype
            FROM iptdiag id
            LEFT JOIN icd101 i10 ON i10.code = id.icd10
            WHERE id.an = ? AND id.diagtype <> "1"
            ORDER BY id.diagtype', [$an]);
        $visit->sec_diags = $secDiags;
        $visit->sdx = implode(', ', array_map(fn($d) => $d->icd10, $secDiags));

        // หัตถการ/ผ่าตัด (Procedures / IOP)
        $procedures = DB::connection('hosxp')->select('
            SELECT io.icd9, COALESCE(i9.name, "") AS name, io.opdate, io.optime, io.enddate, io.endtime,
                   doc.name AS doctor_name, doc.licenseno AS doctor_license
            FROM iptoprt io
            LEFT JOIN icd9cm1 i9 ON i9.code = io.icd9
            LEFT JOIN doctor doc ON doc.code = io.doctor
            WHERE io.an = ?
            ORDER BY io.opdate, io.optime', [$an]);
        $visit->procedures = $procedures;
        $visit->icd9 = implode(', ', array_map(fn($p) => $p->icd9, $procedures));

        // รายการเวชภัณฑ์ / ค่ารักษาพยาบาล / ยา IPD (ดึงจาก opitemrece)
        $items = DB::connection('hosxp')->select('
            SELECT op.item_no, op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price, op.income AS income_cat,
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
            WHERE op.an = ?
            ORDER BY op.item_no', [$an]);

        // การตรวจสอบเงื่อนไขความถูกต้อง 16 แฟ้ม IPD (Validation)
        $errors = [];
        $warnings = [];

        if (empty($visit->cid) || strlen(trim($visit->cid)) !== 13) {
            $errors[] = "เลขประจำตัวประชาชน (CID) ไม่ถูกต้องหรือไม่ครบ 13 หลัก (จำเป็นสำหรับ 16 แฟ้ม)";
        }
        if (empty($visit->hn)) {
            $errors[] = "ไม่พบรหัสผู้ป่วย (HN)";
        }
        if (empty($visit->pdx)) {
            $errors[] = "ยังไม่ได้ระบุรหัสโรคหลัก (PDX) กรุณาสรุปผลการวินิจฉัยโรค";
        }
        if (empty($visit->regdate) || empty($visit->dchdate)) {
            $errors[] = "ข้อมูลวันแรกรับ (Admit) หรือวันจำหน่าย (Discharge) ไม่ครบถ้วน";
        }
        if (empty($visit->dchstts) || empty($visit->dchtype)) {
            $errors[] = "ยังไม่ได้ระบุสถานะหรือประเภทการจำหน่าย (DISCHS / DISCHT)";
        }
        if (empty($visit->adm_w) || floatval($visit->adm_w) <= 0) {
            $warnings[] = "ยังไม่ได้บันทึกน้ำหนักแรกรับ (ADM_W) ในระบบผู้ป่วยใน";
        }
        if (empty($visit->auth_code)) {
            $warnings[] = "ยังไม่มีรหัสขออนุมัติเบิก (Authen Code)";
        }
        if (empty($visit->adjrw) || floatval($visit->adjrw) <= 0) {
            $warnings[] = "ยังไม่ได้ประมวลผลค่าน้ำหนักสัมพัทธ์ (AdjRW / DRG)";
        }

        $auth_valid = !empty($visit->auth_code) && $visit->auth_code === 'Y';

        return response()->json([
            'success' => true,
            'visit' => $visit,
            'sec_diags' => array_map(fn($d) => $d->icd10, $secDiags),
            'procedures' => array_map(fn($p) => $p->icd9, $procedures),
            'items' => $items,
            'validation' => [
                'is_valid' => count($errors) === 0,
                'auth_valid' => $auth_valid,
                'errors' => $errors,
                'warnings' => $warnings
            ]
        ]);
    }
}
