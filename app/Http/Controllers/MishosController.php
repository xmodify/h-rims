<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MishosController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_mishos !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'MIS Hospital'], 403);
                }
                return $next($request);
            }
        ]);
    }
    //-

        public function ucs_ae(Request $request)
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
            return view('mishos.ucs_ae', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,(IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) - IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0)) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_total
                FROM ovst o           
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn  
                
                
                LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
                LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
                LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5) 
                WHERE (o.an ="" OR o.an IS NULL)
                AND proj.vn IS NULL
                AND kidney.vn IS NULL 
                AND p.hipdata_code IN ("UCS","WEL") 							
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            0 AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
            stm.receive_total,stm.repno,IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))   
		    LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)    
            WHERE (o.an ="" OR o.an IS NULL)
			AND proj.vn IS NULL
			AND kidney.vn IS NULL 
			AND p.hipdata_code IN ("UCS","WEL") 							
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
			AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ae_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_walkin(Request $request)
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
            return view('mishos.ucs_walkin', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,(IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) - IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0)) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_total
                FROM ovst o           
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn           
                
                
                LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
                LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN"))
                LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)   
                WHERE (o.an ="" OR o.an IS NULL)
                AND proj.vn IS NOT NULL
                AND kidney.vn IS NULL 
                AND p.hipdata_code IN ("UCS","WEL") 							
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
                AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            0 AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
            stm.receive_total,stm.repno,IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN"))
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN ( SELECT cid, vstdate, LEFT(TIME(datetimeadm),5) AS vsttime5,SUM(receive_total) AS receive_total,
                GROUP_CONCAT(DISTINCT repno) AS repno FROM hrims.stm_ucs
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(TIME(datetimeadm),5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)        
            WHERE (o.an ="" OR o.an IS NULL)
			AND proj.vn IS NOT NULL
			AND kidney.vn IS NULL 
			AND p.hipdata_code IN ("UCS","WEL") 							
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
			AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_walkin_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_herb(Request $request)
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
            return view('mishos.ucs_herb', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,COALESCE(herb.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
				LEAST(IF(stm.receive_hc_drug=0, stm.receive_hc_hc, stm.receive_hc_drug),COALESCE(herb.claim_price,0)) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN (
                    SELECT DISTINCT op.vn 
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.herb32 = "Y"
                    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
                ) o1 ON o1.vn=o.vn
                
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
					INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.herb32 = "Y"
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02" GROUP BY op.vn) herb ON herb.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_hc_drug) AS receive_hc_drug,
                    SUM(receive_hc_hc) AS receive_hc_hc FROM hrims.stm_ucs  
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	
			    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
			    AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ORDER BY o.vstdate,o.vsttime ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(herb.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
            LEAST(IF(stm.receive_hc_drug = 0, stm.receive_hc_hc, stm.receive_hc_drug),COALESCE(herb.claim_price, 0)) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.herb32 = "Y"
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
				WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" AND op.paidst = "02" GROUP BY op.vn) herb ON herb.vn=o.vn						
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_hc_drug) AS receive_hc_drug,
                SUM(receive_hc_hc) AS receive_hc_hc FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_herb_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

    public function ucs_healthmed_procedure(Request $request)
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
            return view('mishos.ucs_healthmed_procedure', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        // รหัสเคลมหัตถการ/ยา สมุนไพร ตามรูปที่ 2
        $claim_codes = "'9007838', '9007712', '9007713', '9007714', '9007716', '9007730', '9007820', '9007811', '8727811', '8737811', '8747811', '8737835', '9007800'";

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT 
                CASE WHEN MONTH(service_date)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(service_date)+543, 2))
                    WHEN MONTH(service_date)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(service_date)+543, 2))
                    END AS month,
                SUM(IF(has_postpartum > 0, 1, 0)) AS postpartum_count,
                SUM(IF(has_massage > 0 AND has_compress > 0, 1, 0)) AS massage_and_compress_count,
                SUM(IF(has_massage > 0 AND has_compress = 0, 1, 0)) AS massage_only_count,
                SUM(IF(has_compress > 0 AND has_massage = 0, 1, 0)) AS compress_only_count,
                SUM(IF(has_poultice > 0, 1, 0)) AS poultice_count,
                SUM(IF(has_steam > 0, 1, 0)) AS steam_count,
                SUM(IF(has_herbs > 0, 1, 0)) AS herbs_count
            FROM (
                SELECT hms.service_date, hms.vn,
                    SUM(IF(hmoi.icd10tm IN (\'9007712\',\'9007713\',\'9007714\',\'9007716\',\'9007730\'), 1, 0)) AS has_postpartum,
                    SUM(IF(hmoi.icd10tm IN (\'8727811\',\'8737811\',\'8747811\',\'8737835\'), 1, 0)) AS has_poultice,
                    SUM(IF(hmoi.icd10tm LIKE \'%7800\', 1, 0)) AS has_steam,
                    SUM(IF(hmoi.icd10tm LIKE \'%7811\', 1, 0)) AS has_massage,
                    SUM(IF(hmoi.icd10tm LIKE \'%7820\', 1, 0)) AS has_compress,
                    SUM(IF(hmoi.icd10tm = \'9007838\', 1, 0)) AS has_herbs
                FROM health_med_service hms
                INNER JOIN health_med_service_operation hmso ON hmso.health_med_service_id = hms.health_med_service_id
                INNER JOIN health_med_operation_item hmoi ON hmoi.health_med_operation_item_id = hmso.health_med_operation_item_id
                WHERE hms.service_date BETWEEN ? AND ?
                GROUP BY hms.vn
            ) as ttm_visits
            GROUP BY YEAR(service_date), MONTH(service_date)
            ORDER BY YEAR(service_date), MONTH(service_date)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $postpartum_count = array_map('intval', array_column($sum_month, 'postpartum_count'));
            $massage_and_compress_count = array_map('intval', array_column($sum_month, 'massage_and_compress_count'));
            $massage_only_count = array_map('intval', array_column($sum_month, 'massage_only_count'));
            $compress_only_count = array_map('intval', array_column($sum_month, 'compress_only_count'));
            $poultice_count = array_map('intval', array_column($sum_month, 'poultice_count'));
            $steam_count = array_map('intval', array_column($sum_month, 'steam_count'));
            $herbs_count = array_map('intval', array_column($sum_month, 'herbs_count'));
        }

            $search_sql = '

            SELECT o.vn AS seq, o.vstdate, o.vsttime, o.oqueue, pt.cid, pt.hn, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
            p.`name` AS pttype, vp.hospmain, v.pdx,
            IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,
            IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(herb.billing_list, "-") AS claim_billing_list,
            COALESCE(herb.claim_price, 0) AS claim_billing_price, 
            COALESCE(herb.claim_price, 0) AS claim_price, 
            CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
            LEAST(IF(stm.receive_hc_drug = 0, stm.receive_hc_hc, stm.receive_hc_drug), COALESCE(herb.claim_price, 0)) AS receive_total,
            ttm.claim_list,
            ttm.has_postpartum, ttm.has_poultice, ttm.has_steam, ttm.has_massage, ttm.has_compress, ttm.has_herbs,
            IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            INNER JOIN (
                SELECT ttm_items.vn,
                    MAX(ttm_items.has_postpartum) as has_postpartum,
                    MAX(ttm_items.has_poultice) as has_poultice,
                    MAX(ttm_items.has_steam) as has_steam,
                    MAX(ttm_items.has_massage) as has_massage,
                    MAX(ttm_items.has_compress) as has_compress,
                    MAX(ttm_items.has_herbs) as has_herbs,
                    GROUP_CONCAT(DISTINCT ttm_items.proc_combined) as claim_list
                FROM (
                    SELECT hms.vn,
                        IF(hmoi.icd10tm IN (\'9007712\',\'9007713\',\'9007714\',\'9007716\',\'9007730\'), 1, 0) AS has_postpartum,
                        IF(hmoi.icd10tm IN (\'8727811\',\'8737811\',\'8747811\',\'8737835\'), 1, 0) AS has_poultice,
                        IF(hmoi.icd10tm LIKE \'%7800\', 1, 0) AS has_steam,
                        IF(hmoi.icd10tm LIKE \'%7811\', 1, 0) AS has_massage,
                        IF(hmoi.icd10tm LIKE \'%7820\', 1, 0) AS has_compress,
                        IF(hmoi.icd10tm = \'9007838\', 1, 0) AS has_herbs,
                        CONCAT(\'[\', hmoi.icd10tm, \'] \', hmoi.health_med_operation_item_name) as proc_combined
                    FROM health_med_service hms
                    INNER JOIN health_med_service_operation hmso ON hmso.health_med_service_id = hms.health_med_service_id
                    INNER JOIN health_med_operation_item hmoi ON hmoi.health_med_operation_item_id = hmso.health_med_operation_item_id
                    WHERE hms.service_date BETWEEN ? AND ?
                      AND (
                           hmoi.icd10tm LIKE "%7811"
                        OR hmoi.icd10tm LIKE "%7820"
                        OR hmoi.icd10tm LIKE "%7800"
                        OR hmoi.icd10tm IN ("9007712","9007713","9007714","9007716","9007730")
                        OR hmoi.icd10tm IN ("8727811","8737811","8747811","8737835")
                        OR hmoi.icd10tm = "9007838"
                      )
                ) ttm_items
                GROUP BY ttm_items.vn
            ) ttm ON ttm.vn = o.vn
            
            LEFT JOIN (
                SELECT op.vn, 
                       GROUP_CONCAT(DISTINCT CONCAT(\'[\', n.nhso_adp_code, \'] \', n.name)) AS billing_list,
                       SUM(op.sum_price) AS claim_price	
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                  AND op.paidst = "02" 
                  AND n.nhso_adp_code LIKE "58%"
                GROUP BY op.vn
            ) herb ON herb.vn=o.vn						
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_hc_drug) AS receive_hc_drug,
                SUM(receive_hc_hc) AS receive_hc_hc FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $postpartum_list = [];
        $compress_list = [];
        $massage_list = [];
        $massage_and_compress_list = [];
        $poultice_list = [];
        $steam_list = [];
        $herbs_list = [];

        foreach ($all_visits as $row) {
            if ($row->has_postpartum > 0) {
                $postpartum_list[] = $row;
            }
            if ($row->has_massage > 0 && $row->has_compress > 0) {
                $massage_and_compress_list[] = $row;
            }
            if ($row->has_massage > 0 && $row->has_compress == 0) {
                $massage_list[] = $row;
            }
            if ($row->has_compress > 0 && $row->has_massage == 0) {
                $compress_list[] = $row;
            }
            if ($row->has_poultice > 0) {
                $poultice_list[] = $row;
            }
            if ($row->has_steam > 0) {
                $steam_list[] = $row;
            }
            if ($row->has_herbs > 0) {
                $herbs_list[] = $row;
            }
        }

        $table_html = view('mishos.ucs_healthmed_procedure_table', compact(
            'budget_year', 'start_date', 'end_date',
            'postpartum_list', 'compress_list', 'massage_list',
            'massage_and_compress_list', 'poultice_list', 'steam_list', 'herbs_list'
        ))->render();

        $patient_items = array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq, 'an' => ''], $all_visits);

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => !$request->input('skip_chart') ? [
                'months' => $month,
                'postpartum_count' => $postpartum_count,
                'massage_and_compress_count' => $massage_and_compress_count,
                'massage_only_count' => $massage_only_count,
                'compress_only_count' => $compress_only_count,
                'poultice_count' => $poultice_count,
                'steam_count' => $steam_count,
                'herbs_count' => $herbs_count
            ] : null
        ]);
    }

    public function ucs_healthmed_procedure_export(Request $request)
    {
        ini_set('max_execution_time', 0);

        $budget_year = $request->budget_year ?: date('Y');
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search_sql = '
            SELECT o.vn AS seq, o.vstdate, o.vsttime, o.oqueue, pt.cid, pt.hn, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
            p.`name` AS pttype, vp.hospmain, v.pdx,
            COALESCE(herb.billing_list, "-") AS claim_billing_list,
            COALESCE(herb.claim_price, 0) AS claim_billing_price,
            COALESCE(herb.claim_price, 0) AS claim_price,
            ttm.claim_list,
            ttm.has_postpartum, ttm.has_poultice, ttm.has_steam, ttm.has_massage, ttm.has_compress, ttm.has_herbs
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            INNER JOIN (
                SELECT ttm_items.vn,
                    MAX(ttm_items.has_postpartum) as has_postpartum,
                    MAX(ttm_items.has_poultice) as has_poultice,
                    MAX(ttm_items.has_steam) as has_steam,
                    MAX(ttm_items.has_massage) as has_massage,
                    MAX(ttm_items.has_compress) as has_compress,
                    MAX(ttm_items.has_herbs) as has_herbs,
                    GROUP_CONCAT(DISTINCT ttm_items.proc_combined) as claim_list
                FROM (
                    SELECT hms.vn,
                        IF(hmoi.icd10tm IN (\'9007712\',\'9007713\',\'9007714\',\'9007716\',\'9007730\'), 1, 0) AS has_postpartum,
                        IF(hmoi.icd10tm IN (\'8727811\',\'8737811\',\'8747811\',\'8737835\'), 1, 0) AS has_poultice,
                        IF(hmoi.icd10tm LIKE \'%7800\', 1, 0) AS has_steam,
                        IF(hmoi.icd10tm LIKE \'%7811\', 1, 0) AS has_massage,
                        IF(hmoi.icd10tm LIKE \'%7820\', 1, 0) AS has_compress,
                        IF(hmoi.icd10tm = \'9007838\', 1, 0) AS has_herbs,
                        CONCAT(\'[\', hmoi.icd10tm, \'] \', hmoi.health_med_operation_item_name) as proc_combined
                    FROM health_med_service hms
                    INNER JOIN health_med_service_operation hmso ON hmso.health_med_service_id = hms.health_med_service_id
                    INNER JOIN health_med_operation_item hmoi ON hmoi.health_med_operation_item_id = hmso.health_med_operation_item_id
                    WHERE hms.service_date BETWEEN ? AND ?
                      AND (
                           hmoi.icd10tm LIKE "%7811"
                        OR hmoi.icd10tm LIKE "%7820"
                        OR hmoi.icd10tm LIKE "%7800"
                        OR hmoi.icd10tm IN ("9007712","9007713","9007714","9007716","9007730")
                        OR hmoi.icd10tm IN ("8727811","8737811","8747811","8737835")
                        OR hmoi.icd10tm = "9007838"
                      )
                ) ttm_items
                GROUP BY ttm_items.vn
            ) ttm ON ttm.vn = o.vn
            LEFT JOIN (
                SELECT op.vn, 
                       GROUP_CONCAT(DISTINCT CONCAT(\'[\', n.nhso_adp_code, \'] \', n.name)) AS billing_list,
                       SUM(op.sum_price) AS claim_price	
                FROM opitemrece op
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                  AND op.paidst = "02" 
                  AND n.nhso_adp_code LIKE "58%"
                GROUP BY op.vn
            ) herb ON herb.vn=o.vn
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
        ';

        $search_placeholders = substr_count($search_sql, '?');
        $search_bindings = [];
        for ($k = 0; $k < $search_placeholders; $k += 2) {
            $search_bindings[] = $start_date;
            $search_bindings[] = $end_date;
        }
        $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $postpartum_list = [];
        $compress_list = [];
        $massage_list = [];
        $massage_and_compress_list = [];
        $poultice_list = [];
        $steam_list = [];
        $herbs_list = [];

        foreach ($all_visits as $row) {
            if ($row->has_postpartum > 0) {
                $postpartum_list[] = $row;
            }
            if ($row->has_massage > 0 && $row->has_compress > 0) {
                $massage_and_compress_list[] = $row;
            }
            if ($row->has_massage > 0 && $row->has_compress == 0) {
                $massage_list[] = $row;
            }
            if ($row->has_compress > 0 && $row->has_massage == 0) {
                $compress_list[] = $row;
            }
            if ($row->has_poultice > 0) {
                $poultice_list[] = $row;
            }
            if ($row->has_steam > 0) {
                $steam_list[] = $row;
            }
            if ($row->has_herbs > 0) {
                $herbs_list[] = $row;
            }
        }

        $tabs_data = [
            'ดูแลมารดาหลังคลอด' => $postpartum_list,
            'ประคบ' => $compress_list,
            'นวด' => $massage_list,
            'นวดและประคบ' => $massage_and_compress_list,
            'พอกเข่า' => $poultice_list,
            'อบสมุนไพร' => $steam_list,
            'การใช้ยาจากสมุนไพร' => $herbs_list,
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet

        foreach ($tabs_data as $tab_title => $data) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $tab_title);
            $spreadsheet->addSheet($sheet);

            // Write headers
            $headers = ['ลำดับ', 'วันที่รับบริการ', 'เวลา', 'Queue', 'HN', 'ชื่อ-สกุล', 'สิทธิการรักษา', 'หัตถการ', 'รายการเรียกเก็บ', 'ยอดเรียกเก็บ'];
            foreach ($headers as $col_index => $header) {
                $col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index + 1);
                $sheet->setCellValue($col_letter . '1', $header);
                $sheet->getStyle($col_letter . '1')->getFont()->setBold(true);
            }

            // Write rows
            $row_num = 2;
            foreach ($data as $index => $row) {
                $sheet->setCellValue('A' . $row_num, $index + 1);
                $sheet->setCellValue('B' . $row_num, DateThai($row->vstdate));
                $sheet->setCellValue('C' . $row_num, $row->vsttime);
                $sheet->setCellValue('D' . $row_num, $row->oqueue);
                $sheet->setCellValue('E' . $row_num, $row->hn);
                $sheet->setCellValue('F' . $row_num, $row->ptname);
                $sheet->setCellValue('G' . $row_num, $row->pttype);
                $sheet->setCellValue('H' . $row_num, $row->claim_list);
                $sheet->setCellValue('I' . $row_num, $row->claim_billing_list);
                $sheet->setCellValue('J' . $row_num, floatval($row->claim_billing_price));
                $sheet->getStyle('J' . $row_num)->getNumberFormat()->setFormatCode('#,##0.00');
                $row_num++;
            }

            // Auto-size columns
            foreach ($headers as $col_index => $header) {
                $col_letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_index + 1);
                $sheet->getColumnDimension($col_letter)->setAutoSize(true);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = "บริการแพทย์แผนไทย_" . date('Ymd_His') . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function ucs_telemed(Request $request)
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
            return view('mishos.ucs_telemed', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(tele.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
				LEAST(stm.receive_op, tele.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("TELMED"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("TELMED")) GROUP BY op.vn) tele ON tele.vn=o.vn						
                LEFT JOIN ( SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_op) AS receive_op
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	
			    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(tele.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                LEAST(stm.receive_op, tele.claim_price) AS receive_total,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
				IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("TELMED")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("TELMED")) GROUP BY op.vn) tele ON tele.vn=o.vn						
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN ( SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_op) AS receive_op
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_telemed_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_rider(Request $request)
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
            return view('mishos.ucs_rider', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(rider.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
				LEAST(stm.receive_op, rider.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("DRUGP"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("DRUGP")) GROUP BY op.vn) rider ON rider.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_op) AS receive_op   
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	
			    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(rider.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                LEAST(stm.receive_op, rider.claim_price) AS receive_total,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
				IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("DRUGP")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("DRUGP")) GROUP BY op.vn) rider ON rider.vn=o.vn						
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_op) AS receive_op   
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_rider_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_gdm(Request $request)
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
            return view('mishos.ucs_gdm', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                stm.receive_dmis_compensate_pay AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_dmis_compensate_pay) AS receive_dmis_compensate_pay
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code IN ("UCS","WEL") 	       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_dmis_compensate_pay AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_dmis_compensate_pay) AS receive_dmis_compensate_pay
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code IN ("UCS","WEL") 	 
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_gdm_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_drug_clopidogrel(Request $request)
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

        
        $drug_clopidogrel = DB::table('main_setting')->where('name', 'drug_clopidogrel')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('mishos.ucs_drug_clopidogrel', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(drug.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,LEAST(stm.receive_hc_drug, drug.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode = ?	AND o1.paidst = "02"					
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.icode = ? AND op.paidst = "02" GROUP BY op.vn) drug ON drug.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_hc_drug) AS receive_hc_drug
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                    
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL")     
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_bindings = [
                $drug_clopidogrel,
                $start_date_b,
                $end_date_b,
                $drug_clopidogrel,
                $start_date_b,
                $end_date_b,
                $start_date_b,
                $end_date_b
            ];
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(drug.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                LEAST(stm.receive_hc_drug, drug.claim_price) AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode = ?	AND o1.paidst = "02"		
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.icode=? AND op.paidst = "02" GROUP BY op.vn) drug ON drug.vn=o.vn
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn						
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_hc_drug) AS receive_hc_drug
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL")            
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_bindings = [
                $drug_clopidogrel,
                $start_date,
                $end_date,
                $drug_clopidogrel,
                $start_date,
                $end_date,
                $start_date,
                $end_date
            ];
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_drug_clopidogrel_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_drug_sk(Request $request)
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
            return view('mishos.ucs_drug_sk', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(sk.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_dmis_drug AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("STEMI1"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
					AND op.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("STEMI1")) GROUP BY op.vn) sk ON sk.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_dmis_drug) AS receive_dmis_drug
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)  
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	
			    AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(sk.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                stm.receive_dmis_drug AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN drugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("STEMI1")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("STEMI1")) GROUP BY op.vn) sk ON sk.vn=o.vn
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn						
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_dmis_drug) AS receive_dmis_drug
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL") 	
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_drug_sk_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ins(Request $request)
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
            return view('mishos.ucs_ins', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ins.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_inst AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT op.vn FROM opitemrece op
                    INNER JOIN nondrugitems n ON n.icode = op.icode
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02" 
                    AND n.nhso_adp_type_id = "2" AND li.uc_cr = "Y"
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (
                    SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
                    INNER JOIN nondrugitems n ON n.icode = op.icode
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
					AND n.nhso_adp_type_id = "2" AND li.uc_cr = "Y"
                    GROUP BY op.vn
                ) ins ON ins.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_inst) AS receive_inst
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(ins.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                stm.receive_inst AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
                pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_type_id = "2"
			INNER JOIN hrims.lookup_icode li ON li.icode = o1.icode AND li.uc_cr = "Y"
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (
                SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
                INNER JOIN nondrugitems n ON n.icode = op.icode
                INNER JOIN hrims.lookup_icode li2 ON li2.icode = op.icode
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND n.nhso_adp_type_id = "2" AND li2.uc_cr = "Y"
                GROUP BY op.vn
            ) ins ON ins.vn=o.vn
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn					
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_inst) AS receive_inst
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL")       
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->validateUcsInsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ins_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_palliative(Request $request)
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
            return view('mishos.ucs_palliative', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

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
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price, SUM(CASE WHEN is_sent = 1 THEN IFNULL(claim_price,0) ELSE 0 END) AS claim_sent_price, SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,stm.receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30001","Cons01","Eva001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30001","Cons01","Eva001")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_palliative) AS receive_total
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                 
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			    AND p.hipdata_code IN ("UCS","WEL") 	       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $month = array_column($sum_month, 'month');
            $claim_price = array_column($sum_month, 'claim_price');
            $claim_sent_price = array_column($sum_month, 'claim_sent_price');
            $receive_total = array_column($sum_month, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                stm.receive_palliative AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(fdh.seq IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30001","Cons01","Eva001")
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30001","Cons01","Eva001")) 
                GROUP BY op.vn) ppfs ON ppfs.vn=o.vn
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn						
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_palliative) AS receive_palliative
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid                 
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
            AND p.hipdata_code IN ("UCS","WEL")       
            AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        $this->checkClosedStatusOnly($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_palliative_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_fp(Request $request)
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
            return view('mishos.ucs_ppfs_fp', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();
        ini_set('memory_limit', '1024M');

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
                    AND (o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
			        OR o1.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			        WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND (op.icode IN (SELECT icode FROM nondrugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
				    OR op.icode IN (SELECT icode FROM drugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                AND o1.vn IS NOT NULL
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["FP001", "FP002", "FP002_1", "FP002_2", "FP003_1", "FP003_2", "FP003_3", "FP003_4"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
                AND (o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
			    OR o1.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND (op.icode IN (SELECT icode FROM nondrugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
				OR op.icode IN (SELECT icode FROM drugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["FP001", "FP002", "FP002_1", "FP002_2", "FP003_1", "FP003_2", "FP003_3", "FP003_4"], 'vn');
        $this->allocatePpfs($all_visits, ["FP001", "FP002", "FP002_1", "FP002_2", "FP003_1", "FP003_2", "FP003_3", "FP003_4"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_fp_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_prt(Request $request)
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
        $lab_prt = DB::table('main_setting')->where('name', 'lab_prt')->value('value');

        if (!$request->ajax() && !$request->wantsJson()) {
            return view('mishos.ucs_ppfs_prt', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["30014"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,lo.lab_items_name_ref,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN lab_head lh ON lh.vn=o.vn
			LEFT JOIN lab_order lo ON lo.lab_order_number=lh.lab_order_number AND lo.lab_items_code IN (' . $lab_prt . ') 
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["30014"], 'vn');
        $this->allocatePpfs($all_visits, ["30014"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_prt_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_ida(Request $request)
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
            return view('mishos.ucs_ppfs_ida', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["13001"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["13001"], 'vn');
        $this->allocatePpfs($all_visits, ["13001"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_ida_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_ferrofolic(Request $request)
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
            return view('mishos.ucs_ppfs_ferrofolic', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["14001"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["14001"], 'vn');
        $this->allocatePpfs($all_visits, ["14001"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_ferrofolic_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_fluoride(Request $request)
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
            return view('mishos.ucs_ppfs_fluoride', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02"
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("15001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["15001"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["15001"], 'vn');
        $this->allocatePpfs($all_visits, ["15001"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_fluoride_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_anc(Request $request)
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
            return view('mishos.ucs_ppfs_anc', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["30008", "30009", "30010", "30011", "30012", "30013"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,a.anc_service_number,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN person_anc_service a ON a.vn=o.vn
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["30008", "30009", "30010", "30011", "30012", "30013"], 'vn');
        $this->allocatePpfs($all_visits, ["30008", "30009", "30010", "30011", "30012", "30013"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_anc_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_postnatal(Request $request)
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
            return view('mishos.ucs_ppfs_postnatal', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["30015", "30016"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["30015", "30016"], 'vn');
        $this->allocatePpfs($all_visits, ["30015", "30016"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_postnatal_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_fittest(Request $request)
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
            return view('mishos.ucs_ppfs_fittest', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["90005"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005"))
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
            LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
            WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
            AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["90005"], 'vn');
        $this->allocatePpfs($all_visits, ["90005"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_fittest_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

        public function ucs_ppfs_scr(Request $request)
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
            return view('mishos.ucs_ppfs_scr', compact('budget_year_select', 'budget_year', 'start_date', 'end_date'));
        }

        session()->save();

        $month = [];
        $claim_price = [];
        $claim_sent_price = [];
        $receive_total = [];

        if (!$request->input('skip_chart')) {
            $sum_month_sql = '

            SELECT vn, vstdate, claim_price, is_sent, 0.00 AS receive_total FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,
                0.00 AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
                
				INNER JOIN (
                    SELECT vn FROM opitemrece 
                    WHERE vstdate BETWEEN ? AND ? AND paidst = "02" AND icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004"))
                ) o1 ON o1.vn=o.vn
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                    FROM hrims.stm_ucs 
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                    AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
            ';
            $chart_placeholders = substr_count($sum_month_sql, '?');
            $chart_bindings = [];
            for ($k = 0; $k < $chart_placeholders; $k += 2) {
                $chart_bindings[] = $start_date_b;
                $chart_bindings[] = $end_date_b;
            }
            $sum_month = DB::connection('hosxp')->select($sum_month_sql, $chart_bindings);

            $this->allocatePpfs($sum_month, ["12003", "12004"], 'vn');
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
                    $grouped[$key] = ['month' => $monthStr, 'claim_price' => 0, 'claim_sent_price' => 0, 'receive_total' => 0];
                }
                $grouped[$key]['claim_price'] += floatval($row->claim_price);
                $grouped[$key]['claim_sent_price'] += floatval($row->is_sent == 1 ? $row->claim_price : 0);
                $grouped[$key]['receive_total'] += floatval($row->receive_total);
            }
            ksort($grouped);
            $month = array_column($grouped, 'month');
            $claim_price = array_column($grouped, 'claim_price');
            $claim_sent_price = array_column($grouped, 'claim_sent_price');
            $receive_total = array_column($grouped, 'receive_total');
        }

            $search_sql = '

            SELECT o.vn AS seq,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,"" AS icd10,IFNULL((SELECT SUM(sum_price) FROM opitemrece WHERE vn = o.vn AND paidst = "02"),0) AS income,IFNULL((SELECT SUM(total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND a.rcpno IS NULL),0) AS rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price, CASE WHEN (SELECT 1 FROM hrims.fdh_claim_status WHERE seq = o.vn LIMIT 1) IS NOT NULL OR stm.cid IS NOT NULL THEN 1 ELSE 0 END AS is_sent,0.00 AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(fdh.seq IS NOT NULL,"Y","") AS claim,
            pt.sex, v.age_y, IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%"),"Y",NULL) AS auth_code_ep
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            
            
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.paidst = "02" 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN (SELECT seq FROM hrims.fdh_claim_status WHERE seq IS NOT NULL GROUP BY seq) fdh ON fdh.seq = o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ? AND op.paidst = "02"
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN (SELECT cid, vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp
                FROM hrims.stm_ucs 
                WHERE vstdate BETWEEN ? AND ?
                GROUP BY cid, vstdate, LEFT(vsttime,5)) stm ON stm.cid = pt.cid
                AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND o1.vn IS NOT NULL
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime
            ';
            $search_placeholders = substr_count($search_sql, '?');
            $search_bindings = [];
            for ($k = 0; $k < $search_placeholders; $k += 2) {
                $search_bindings[] = $start_date;
                $search_bindings[] = $end_date;
            }
            $all_visits = DB::connection('hosxp')->select($search_sql, $search_bindings);

        $hns = array_filter(array_unique(array_column($all_visits, 'hn')));
        $repData = [];
        if (!empty($hns)) {
            $repRecords = DB::table('hrims.rep_ucs')
                ->whereIn('hn', $hns)
                ->where('rep_type', 'OP')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->select('hn', 'vstdate', 'vsttime', 'error_code', 'repno')
                ->get()
                ->groupBy('hn');
            foreach ($repRecords as $hn => $group) {
                foreach ($group as $rep) {
                    $repData[$hn][$rep->vstdate][substr($rep->vsttime, 0, 5)] = $rep;
                }
            }
        }

        foreach ($all_visits as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $row->rep_error_code = $repData[$row->hn][$row->vstdate][$vsttime5]->error_code ?? null;
            $row->rep_repno = $repData[$row->hn][$row->vstdate][$vsttime5]->repno ?? null;
            if (!property_exists($row, 'repno')) {
                $row->repno = null;
            }
            $row->claim_price = floatval($row->claim_price);
        }

        // Extra allocations
        $this->allocatePpfs($sum_month, ["12003", "12004"], 'vn');
        $this->allocatePpfs($all_visits, ["12003", "12004"], 'seq');

        $this->validateUcsPpfsRows($all_visits);

        $search = [];
        $claim = [];
        foreach ($all_visits as $row) {
            $isSent = ($row->is_sent == 1) || ($row->claim == 'Y') || !empty($row->repno) || ($row->receive_total > 0) || !empty($row->rep_repno);
            if ($isSent) {
                $claim[] = $row;
            } else {
                $search[] = $row;
            }
        }

        $table_html = view('mishos.ucs_ppfs_scr_table', compact('budget_year', 'start_date', 'end_date', 'search', 'claim'))->render();
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

    public function ucs_ppfs_visit_details(\Illuminate\Http\Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json(['error' => 'กรุณาระบุ VN'], 400);
        }

        $visit = \Illuminate\Support\Facades\DB::connection('hosxp')->selectOne('
            SELECT o.vn, o.vstdate, o.vsttime, o.oqueue,
                   pt.hn, pt.sex, v.age_y, pt.cid,
                   CONCAT(pt.pname,pt.fname," ",pt.lname) AS ptname,
                   p.name AS pttype, vp.hospmain, os.cc, (SELECT icd10 FROM ovstdiag WHERE vn = o.vn AND diagtype = "1" LIMIT 1) AS pdx,
                   v.income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                   IF((vp.auth_code IS NOT NULL AND vp.auth_code <> ""),"Y",NULL) AS auth_code,
                   IF((ep.claimCode LIKE "EP%" OR ep.claim_status = "success"),"Y",NULL) AS endpoint,
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
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND (ep.claimCode LIKE "EP%" OR ep.claim_status = "success")
            LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn = ?', [$vn]);

        if (!$visit) {
            return response()->json(['error' => 'ไม่พบข้อมูลรับบริการ'], 404);
        }

        $secDiags = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->whereNotIn('diagtype', ['1', '2'])
            ->pluck('icd10')
            ->toArray();
        $visit->sdx = implode(',', $secDiags);

        $procedures = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('ovstdiag')
            ->where('vn', $vn)
            ->where('diagtype', '2')
            ->pluck('icd10')
            ->toArray();
        $visit->icd9 = implode(',', $procedures);

        $items = \Illuminate\Support\Facades\DB::connection('hosxp')->select('
            SELECT op.vn, op.icode, IFNULL(n.name, d.name) AS name,
                   op.qty, op.unitprice, op.sum_price,
                   li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
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

        $adpCodes = collect($items)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
        $insUcsMap = [];
        if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
            $insRecords = \Illuminate\Support\Facades\DB::table('lookup_nhso_adp_code')
                ->whereIn('nhso_adp_code', $adpCodes)
                ->where('nhso_adp_type_id', 2)
                ->pluck('ins_ucs', 'nhso_adp_code');
            $insUcsMap = $insRecords->toArray();
        }
        foreach ($items as $item) {
            $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
        }

        $hasPpfs = false;
        foreach ($items as $item) {
            if (($item->ppfs ?? '') === 'Y') {
                $hasPpfs = true;
                break;
            }
        }

        $validator = new \App\Services\ClaimValidator();
        $validation = $hasPpfs ? $validator->validate($visit, $items) : $validator->validateInsUcsOnly($visit, $items);

        return response()->json([
            'visit'      => $visit,
            'sec_diags'  => $secDiags,
            'procedures' => $procedures,
            'items'      => $items,
            'validation' => $validation,
        ]);
    }

    private function validateUcsPpfsRows(&$search)
    {
        $allVns = array_column($search, 'seq');
        if (empty($allVns)) {
            return;
        }

        // 1. Batch load claim items for all VNs
        $itemsByVn = [];
        $rawItems = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->select('
                SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                       li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
                       IFNULL(n.name, d.name) AS name
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                AND li.ppfs = "Y"',
            $allVns);

        $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
        $insUcsMap = [];
        if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
            $insUcsMap = \Illuminate\Support\Facades\DB::table('lookup_nhso_adp_code')
                ->whereIn('nhso_adp_code', $adpCodes)
                ->where('nhso_adp_type_id', 2)
                ->pluck('ins_ucs', 'nhso_adp_code')
                ->toArray();
        }
        foreach ($rawItems as $item) {
            $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
            $itemsByVn[$item->vn][] = $item;
        }

        // 2. Batch load additional patient details, endpoints, FDH status, sdx, icd9
        $cids = array_filter(array_unique(array_column($search, 'cid')));
        $endpointsMap = [];
        if (!empty($cids)) {
            $endpoints = \Illuminate\Support\Facades\DB::table('hrims.nhso_endpoint')
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
        }

        $fdhStatuses = \Illuminate\Support\Facades\DB::table('hrims.fdh_claim_status')
            ->whereIn('seq', $allVns)
            ->pluck('status_message_th', 'seq')
            ->toArray();

        $rawSdx = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('ovstdiag')
            ->whereIn('vn', $allVns)
            ->whereNotIn('diagtype', ['1', '2'])
            ->select('vn', 'icd10')
            ->get()
            ->groupBy('vn');

        $rawIcd9 = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('ovstdiag')
            ->whereIn('vn', $allVns)
            ->where('diagtype', '2')
            ->select('vn', 'icd10')
            ->get()
            ->groupBy('vn');

        // 3. Run ClaimValidator
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            // Populate fields for validator
            $row->sdx = isset($rawSdx[$row->seq]) ? implode(',', $rawSdx[$row->seq]->pluck('icd10')->toArray()) : '';
            $row->icd9 = isset($rawIcd9[$row->seq]) ? implode(',', $rawIcd9[$row->seq]->pluck('icd10')->toArray()) : '';
            $row->fdh_status = $fdhStatuses[$row->seq] ?? null;

            $hasEp = isset($endpointsMap[$row->cid][$row->vstdate]);
            $row->endpoint = $hasEp ? 'Y' : null;

            // Run validation
            $result = $validator->validate($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
    }

    private function validateUcsInsRows(&$search)
    {
        $allVns = array_column($search, 'seq');
        if (empty($allVns)) {
            return;
        }

        // 1. Batch load claim items (where uc_cr = Y) for all VNs
        $itemsByVn = [];
        $rawItems = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->select('
                SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price,
                       li.ppfs, li.uc_cr, li.herb32, li.nhso_adp_code,
                       IFNULL(n.name, d.name) AS name
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vn IN (' . implode(',', array_fill(0, count($allVns), '?')) . ')
                AND li.uc_cr = "Y"',
            $allVns);

        $adpCodes = collect($rawItems)->pluck('nhso_adp_code')->filter()->unique()->values()->toArray();
        $insUcsMap = [];
        if (!empty($adpCodes) && \Illuminate\Support\Facades\Schema::hasTable('lookup_nhso_adp_code')) {
            $insUcsMap = \Illuminate\Support\Facades\DB::table('lookup_nhso_adp_code')
                ->whereIn('nhso_adp_code', $adpCodes)
                ->where('nhso_adp_type_id', 2)
                ->pluck('ins_ucs', 'nhso_adp_code')
                ->toArray();
        }
        foreach ($rawItems as $item) {
            $item->ins_ucs = $insUcsMap[$item->nhso_adp_code] ?? null;
            $itemsByVn[$item->vn][] = $item;
        }

        // 2. Batch load additional patient details, endpoints, FDH status
        $cids = array_filter(array_unique(array_column($search, 'cid')));
        $endpointsMap = [];
        if (!empty($cids)) {
            $endpoints = \Illuminate\Support\Facades\DB::table('hrims.nhso_endpoint')
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
        }

        $fdhStatuses = \Illuminate\Support\Facades\DB::table('hrims.fdh_claim_status')
            ->whereIn('seq', $allVns)
            ->pluck('status_message_th', 'seq')
            ->toArray();

        // 3. Run ClaimValidator
        $validator = new \App\Services\ClaimValidator();
        foreach ($search as $row) {
            // Populate fields for validator
            $row->sdx = '';
            $row->icd9 = '';
            $row->fdh_status = $fdhStatuses[$row->seq] ?? null;

            $hasEp = isset($endpointsMap[$row->cid][$row->vstdate]);
            $row->endpoint = $hasEp ? 'Y' : null;

            // Run validation
            $result = $validator->validateInsUcsOnly($row, $itemsByVn[$row->seq] ?? []);
            $row->is_valid           = $result['is_valid'];
            $row->endpoint_valid     = $result['endpoint_valid'];
            $row->validation_errors  = $result['errors'];
            $row->validation_warnings = $result['warnings'];
        }
    }

    private function checkClosedStatusOnly(&$search)
    {
        $allVns = array_column($search, 'seq');
        if (empty($allVns)) {
            return;
        }

        $cids = array_filter(array_unique(array_column($search, 'cid')));
        $endpointsMap = [];
        if (!empty($cids)) {
            $endpoints = \Illuminate\Support\Facades\DB::table('hrims.nhso_endpoint')
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
        }

        // Get auth_code status from visit_pttype
        $authCodes = \Illuminate\Support\Facades\DB::connection('hosxp')
            ->table('visit_pttype')
            ->whereIn('vn', $allVns)
            ->pluck('auth_code', 'vn')
            ->toArray();

        foreach ($search as $row) {
            $hasEp = isset($endpointsMap[$row->cid][$row->vstdate]);
            $authCode = $authCodes[$row->seq] ?? null;
            $row->endpoint_valid = $hasEp;
        }
    }

    private function allocatePpfs(&$rows, $adpCodes, $vnField = 'seq')
    {
        if (empty($rows)) return;
        
        $vns = [];
        foreach ($rows as $row) {
            $vns[] = $row->{$vnField};
        }
        $vns = array_values(array_unique($vns));
        
        $vnChunks = array_chunk($vns, 1000);
        $itemsByVn = [];
        $stmMap = [];
        
        foreach ($vnChunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            
            $rawItems = DB::connection('hosxp')->select("
                SELECT op.vn, op.icode, op.sum_price, li.nhso_adp_code
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.vn IN ($placeholders) AND op.paidst = '02' AND li.ppfs = 'Y'
            ", $chunk);
            
            foreach ($rawItems as $item) {
                $itemsByVn[$item->vn][] = $item;
            }
            
            $rawStm = DB::connection('hosxp')->select("
                SELECT o.vn, SUM(stm.receive_pp) AS receive_pp, GROUP_CONCAT(DISTINCT stm.repno) AS repno
                FROM ovst o
                INNER JOIN patient pt ON pt.hn = o.hn
                INNER JOIN hrims.stm_ucs stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime, 5) = LEFT(o.vsttime, 5)
                WHERE o.vn IN ($placeholders)
                GROUP BY o.vn
            ", $chunk);
            
            foreach ($rawStm as $stm) {
                $stmMap[$stm->vn] = [
                    'receive_pp' => $stm->receive_pp,
                    'repno' => $stm->repno
                ];
            }
        }
        
        foreach ($rows as $row) {
            $vn = $row->{$vnField};
            $items = $itemsByVn[$vn] ?? [];
            $receive_pp = floatval($stmMap[$vn]['receive_pp'] ?? 0);
            $row->repno = $stmMap[$vn]['repno'] ?? null;
            
            if (empty($items)) {
                $row->receive_total = 0;
                continue;
            }
            
            // Sort items: exact match first, then by nhso_adp_code ascending
            usort($items, function($a, $b) use ($receive_pp) {
                $aMatch = (abs(floatval($a->sum_price) - $receive_pp) < 0.01) ? 0 : 1;
                $bMatch = (abs(floatval($b->sum_price) - $receive_pp) < 0.01) ? 0 : 1;
                if ($aMatch !== $bMatch) {
                    return $aMatch <=> $bMatch;
                }
                return strcasecmp($a->nhso_adp_code, $b->nhso_adp_code);
            });
            
            $remaining = $receive_pp;
            $allocated = [];
            foreach ($items as $item) {
                $alloc = min(floatval($item->sum_price), $remaining);
                $allocated[$item->nhso_adp_code] = ($allocated[$item->nhso_adp_code] ?? 0) + $alloc;
                $remaining -= $alloc;
            }
            
            $row_receive_total = 0;
            foreach ($adpCodes as $code) {
                $row_receive_total += $allocated[$code] ?? 0;
            }
            $row->receive_total = $row_receive_total;
        }
    }
}
