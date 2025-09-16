<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpdController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }
//Create op-pp visit----------------------------------------------------------------------------------------------------------------------------------------------------
    public function oppp_visit(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

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
        $start_date   = $year_data[$budget_year]     ?? null;
        $start_date_y = $year_data[$budget_year - 4] ?? null;
        $end_date = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $visit_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)="10" THEN CONCAT("ต.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="11" THEN CONCAT("พ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="12" THEN CONCAT("ธ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="1" THEN CONCAT("ม.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="2" THEN CONCAT("ก.พ. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="3" THEN CONCAT("มี.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="4" THEN CONCAT("เม.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="5" THEN CONCAT("พ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="6" THEN CONCAT("มิ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="7" THEN CONCAT("ก.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="8" THEN CONCAT("ส.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="9" THEN CONCAT("ก.ย. ",RIGHT(YEAR(vstdate)+543,2))
            END AS "month",COUNT(vn) AS "visit",COUNT(DISTINCT hn) AS "hn",
            SUM(CASE WHEN diagtype ="OP" THEN 1 ELSE 0 END) AS "visit_op",
            SUM(CASE WHEN diagtype ="PP" THEN 1 ELSE 0 END) AS "visit_pp",SUM(income) AS "income",
            SUM(inc12) AS "inc_drug",SUM(inc03) AS "inc_lab",
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "ucs",
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "ucs_income",            
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "ucs_inc_drug",
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "ucs_inc_lab",
            SUM(CASE WHEN hipdata_code IN ("OFC","BKK","BMT") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "ofc",
            SUM(CASE WHEN hipdata_code IN ("OFC","BKK","BMT") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "ofc_income",
            SUM(CASE WHEN hipdata_code IN ("OFC","BKK","BMT") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "ofc_inc_drug",
            SUM(CASE WHEN hipdata_code IN ("OFC","BKK","BMT") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "ofc_inc_lab",            
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "sss",
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "sss_income",
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "sss_inc_drug",
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "sss_inc_lab",            
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "lgo",
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "lgo_income",
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "lgo_inc_drug",
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "lgo_inc_lab",            
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "fss",
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "fss_income",
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "fss_inc_drug",
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "fss_inc_lab",            
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS "stp",   
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS "stp_income",
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS "stp_inc_drug", 
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS "stp_inc_lab",
            SUM(CASE WHEN (paidst IN ("01","03") OR hipdata_code IN ("A1","A9")) THEN 1 ELSE 0 END) AS "pay",
            SUM(CASE WHEN (paidst IN ("01","03") OR hipdata_code IN ("A1","A9")) THEN income ELSE 0 END) AS "pay_income",
            SUM(CASE WHEN (paidst IN ("01","03") OR hipdata_code IN ("A1","A9")) THEN inc12 ELSE 0 END) AS "pay_inc_drug",
            SUM(CASE WHEN (paidst IN ("01","03") OR hipdata_code IN ("A1","A9")) THEN inc03 ELSE 0 END) AS "pay_inc_lab"            
            FROM (SELECT v.vstdate,v.vn,v.hn,v.pttype,p.hipdata_code,p.paidst,v.income,v.inc03,v.inc12 ,v.pdx,
            IF(i.icd10 IS NULL,"OP","PP") AS diagtype
            FROM vn_stat v
            LEFT JOIN pttype p ON p.pttype=v.pttype
            LEFT JOIN hrims.lookup_icd10 i ON i.icd10=v.pdx AND i.pp="Y"
            WHERE v.vstdate BETWEEN ? AND ?) AS a									
            GROUP BY YEAR(vstdate) , MONTH(vstdate)
            ORDER BY YEAR(vstdate) , MONTH(vstdate)',[$start_date,$end_date]);
        $month = array_column($visit_month,'month');
        $visit = array_column($visit_month,'visit');
        $hn = array_column($visit_month,'hn');
        $visit_op = array_column($visit_month,'visit_op');
        $visit_pp = array_column($visit_month,'visit_pp');
        $ucs = array_column($visit_month,'ucs');
        $ucs_inc_lab = array_column($visit_month,'ucs_inc_lab');         
        $ucs_inc_drug = array_column($visit_month,'ucs_inc_drug'); 
        $ofc = array_column($visit_month,'ofc');
        $ofc_inc_lab = array_column($visit_month,'ofc_inc_lab');         
        $ofc_inc_drug = array_column($visit_month,'ofc_inc_drug'); 
        $sss = array_column($visit_month,'sss');
        $sss_inc_lab = array_column($visit_month,'sss_inc_lab');         
        $sss_inc_drug = array_column($visit_month,'sss_inc_drug'); 
        $lgo = array_column($visit_month,'lgo');
        $lgo_inc_lab = array_column($visit_month,'lgo_inc_lab');         
        $lgo_inc_drug = array_column($visit_month,'lgo_inc_drug'); 
        $fss = array_column($visit_month,'fss');
        $fss_inc_lab = array_column($visit_month,'fss_inc_lab');         
        $fss_inc_drug = array_column($visit_month,'fss_inc_drug'); 
        $stp = array_column($visit_month,'stp');
        $stp_inc_lab = array_column($visit_month,'stp_inc_lab');         
        $stp_inc_drug = array_column($visit_month,'stp_inc_drug'); 
        $pay = array_column($visit_month,'pay');
        $pay_inc_lab = array_column($visit_month,'pay_inc_lab');         
        $pay_inc_drug = array_column($visit_month,'pay_inc_drug'); 

        return view('opd.oppp_visit',compact('budget_year_select','budget_year','visit_month','month','hn','visit','visit_op','visit_pp','ucs',
            'ucs_inc_lab','ucs_inc_drug','ofc','ofc_inc_lab','ofc_inc_drug','sss','sss_inc_lab','sss_inc_drug','lgo','lgo_inc_lab',
            'lgo_inc_drug','fss','fss_inc_lab','fss_inc_drug','stp','stp_inc_lab','stp_inc_drug','pay','pay_inc_lab','pay_inc_drug'));            
    }
//Create diag_sepsis----------------------------------------------------------------------------------------------------------------------------------------------------
    public function diag_sepsis(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

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
        $start_date   = $year_data[$budget_year]     ?? null;
        $start_date_y = $year_data[$budget_year - 4] ?? null;
        $end_date = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $diag_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)="10" THEN CONCAT("ต.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="11" THEN CONCAT("พ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="12" THEN CONCAT("ธ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="1" THEN CONCAT("ม.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="2" THEN CONCAT("ก.พ. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="3" THEN CONCAT("มี.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="4" THEN CONCAT("เม.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="5" THEN CONCAT("พ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="6" THEN CONCAT("มิ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="7" THEN CONCAT("ก.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="8" THEN CONCAT("ส.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="9" THEN CONCAT("ก.ย. ",RIGHT(YEAR(vstdate)+543,2))
            END AS "month", COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("A419","R651","R572") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)',[$start_date,$end_date]);
        $diag_m = array_column($diag_month,'month');
        $diag_visit_m = array_column($diag_month,'visit');
        $diag_hn_m = array_column($diag_month,'hn');
        $diag_admit_m = array_column($diag_month,'admit');  
        $diag_refer_m = array_column($diag_month,'refer');  
        
        $diag_year = DB::connection('hosxp')->select('
            SELECT IF(MONTH(vstdate)>9,YEAR(vstdate)+1,YEAR(vstdate)) + 543 AS year_bud,
            COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("A419","R651","R572") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud',[$start_date_y,$end_date]);
        $diag_y = array_column($diag_year,'year_bud');
        $diag_visit_y = array_column($diag_year,'visit');
        $diag_hn_y = array_column($diag_year,'hn');
        $diag_admit_y = array_column($diag_year,'admit');  
        $diag_refer_y = array_column($diag_year,'refer');
    
        $diag_list = DB::connection('hosxp')->select('
            SELECT o.vn,o.vstdate,o.vsttime,o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            v.age_y,CONCAT(p.hipdata_code,"-",p.name) AS pttype,oc.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS dx,
            GROUP_CONCAT(DISTINCT od2.icd10) AS icd9,d.`name` AS dx_doctor,o.an AS admit,CONCAT(h.`name`," [",r.pdx,"]") AS refer,
            v.inc03 AS inc_lab,v.inc12 AS inc_drug
            FROM ovst o 
            LEFT JOIN opdscreen oc ON oc.vn=o.vn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn=o.vn AND od.diagtype NOT IN ("1","2")
            LEFT JOIN ovstdiag od2 ON od2.vn=o.vn AND od2.diagtype = "2"
            LEFT JOIN referout r ON r.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=r.refer_hospcode
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN doctor d ON d.`code`=v.dx_doctor
            WHERE o.vstdate BETWEEN ? AND ?
            AND (v.pdx IN ("A419","R651","R572") 
            OR v.dx0 IN ("A419","R651","R572") 
            OR v.dx1 IN ("A419","R651","R572")  
            OR v.dx2 IN ("A419","R651","R572")  
            OR v.dx3 IN ("A419","R651","R572") 
            OR v.dx4 IN ("A419","R651","R572") 
            OR v.dx5 IN ("A419","R651","R572"))
            GROUP BY o.vn',[$start_date,$end_date]);         

        return view('opd.diag_sepsis',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }
//Create diag_stroke-------------------------------------------------------------------------------------------------------------------------------------------------
    public function diag_stroke(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

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
        $start_date   = $year_data[$budget_year]     ?? null;
        $start_date_y = $year_data[$budget_year - 4] ?? null;
        $end_date = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $diag_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)="10" THEN CONCAT("ต.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="11" THEN CONCAT("พ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="12" THEN CONCAT("ธ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="1" THEN CONCAT("ม.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="2" THEN CONCAT("ก.พ. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="3" THEN CONCAT("มี.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="4" THEN CONCAT("เม.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="5" THEN CONCAT("พ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="6" THEN CONCAT("มิ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="7" THEN CONCAT("ก.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="8" THEN CONCAT("ส.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="9" THEN CONCAT("ก.ย. ",RIGHT(YEAR(vstdate)+543,2))
            END AS "month", COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("I64") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)',[$start_date,$end_date]);
        $diag_m = array_column($diag_month,'month');
        $diag_visit_m = array_column($diag_month,'visit');
        $diag_hn_m = array_column($diag_month,'hn');
        $diag_admit_m = array_column($diag_month,'admit');  
        $diag_refer_m = array_column($diag_month,'refer');  
        
        $diag_year = DB::connection('hosxp')->select('
            SELECT IF(MONTH(vstdate)>9,YEAR(vstdate)+1,YEAR(vstdate)) + 543 AS year_bud,
            COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("I64") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud',[$start_date_y,$end_date]);
        $diag_y = array_column($diag_year,'year_bud');
        $diag_visit_y = array_column($diag_year,'visit');
        $diag_hn_y = array_column($diag_year,'hn');
        $diag_admit_y = array_column($diag_year,'admit');  
        $diag_refer_y = array_column($diag_year,'refer');
    
        $diag_list = DB::connection('hosxp')->select('
            SELECT o.vn,o.vstdate,o.vsttime,o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            v.age_y,CONCAT(p.hipdata_code,"-",p.name) AS pttype,oc.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS dx,
            GROUP_CONCAT(DISTINCT od2.icd10) AS icd9,d.`name` AS dx_doctor,o.an AS admit,CONCAT(h.`name`," [",r.pdx,"]") AS refer,
            v.inc03 AS inc_lab,v.inc12 AS inc_drug
            FROM ovst o 
            LEFT JOIN opdscreen oc ON oc.vn=o.vn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn=o.vn AND od.diagtype NOT IN ("1","2")
            LEFT JOIN ovstdiag od2 ON od2.vn=o.vn AND od2.diagtype = "2"
            LEFT JOIN referout r ON r.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=r.refer_hospcode
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN doctor d ON d.`code`=v.dx_doctor
            WHERE o.vstdate BETWEEN ? AND ?
            AND (v.pdx IN ("I64") 
            OR v.dx0 IN ("I64") 
            OR v.dx1 IN ("I64")  
            OR v.dx2 IN ("I64")  
            OR v.dx3 IN ("I64") 
            OR v.dx4 IN ("I64") 
            OR v.dx5 IN ("I64"))
            GROUP BY o.vn',[$start_date,$end_date]);         

        return view('opd.diag_stroke',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

//Create diag_stemi----------------------------------------------------------------------------------------------------------------------------------------------
    public function diag_stemi(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

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
        $start_date   = $year_data[$budget_year]     ?? null;
        $start_date_y = $year_data[$budget_year - 4] ?? null;
        $end_date = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $diag_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)="10" THEN CONCAT("ต.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="11" THEN CONCAT("พ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="12" THEN CONCAT("ธ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="1" THEN CONCAT("ม.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="2" THEN CONCAT("ก.พ. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="3" THEN CONCAT("มี.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="4" THEN CONCAT("เม.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="5" THEN CONCAT("พ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="6" THEN CONCAT("มิ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="7" THEN CONCAT("ก.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="8" THEN CONCAT("ส.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="9" THEN CONCAT("ก.ย. ",RIGHT(YEAR(vstdate)+543,2))
            END AS "month", COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("I210","I211","I212","I213") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)',[$start_date,$end_date]);
        $diag_m = array_column($diag_month,'month');
        $diag_visit_m = array_column($diag_month,'visit');
        $diag_hn_m = array_column($diag_month,'hn');
        $diag_admit_m = array_column($diag_month,'admit');  
        $diag_refer_m = array_column($diag_month,'refer');  
        
        $diag_year = DB::connection('hosxp')->select('
            SELECT IF(MONTH(vstdate)>9,YEAR(vstdate)+1,YEAR(vstdate)) + 543 AS year_bud,
            COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("I210","I211","I212","I213") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud',[$start_date_y,$end_date]);
        $diag_y = array_column($diag_year,'year_bud');
        $diag_visit_y = array_column($diag_year,'visit');
        $diag_hn_y = array_column($diag_year,'hn');
        $diag_admit_y = array_column($diag_year,'admit');  
        $diag_refer_y = array_column($diag_year,'refer');
    
        $diag_list = DB::connection('hosxp')->select('
            SELECT o.vn,o.vstdate,o.vsttime,o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            v.age_y,CONCAT(p.hipdata_code,"-",p.name) AS pttype,oc.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS dx,
            GROUP_CONCAT(DISTINCT od2.icd10) AS icd9,d.`name` AS dx_doctor,o.an AS admit,CONCAT(h.`name`," [",r.pdx,"]") AS refer,
            v.inc03 AS inc_lab,v.inc12 AS inc_drug
            FROM ovst o 
            LEFT JOIN opdscreen oc ON oc.vn=o.vn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn=o.vn AND od.diagtype NOT IN ("1","2")
            LEFT JOIN ovstdiag od2 ON od2.vn=o.vn AND od2.diagtype = "2"
            LEFT JOIN referout r ON r.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=r.refer_hospcode
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN doctor d ON d.`code`=v.dx_doctor
            WHERE o.vstdate BETWEEN ? AND ?
            AND (v.pdx IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx0 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx1 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx2 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx3 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx4 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx5 IN ("I21","I210","I211","I212","I213","I214","I219"))
            GROUP BY o.vn',[$start_date,$end_date]);         

        return view('opd.diag_stemi',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

//Create diag_pneumonia
    public function diag_pneumonia(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

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
        $start_date   = $year_data[$budget_year]     ?? null;
        $start_date_y = $year_data[$budget_year - 4] ?? null;
        $end_date = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->value('DATE_END');

        $diag_month = DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)="10" THEN CONCAT("ต.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="11" THEN CONCAT("พ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="12" THEN CONCAT("ธ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="1" THEN CONCAT("ม.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="2" THEN CONCAT("ก.พ. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="3" THEN CONCAT("มี.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="4" THEN CONCAT("เม.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="5" THEN CONCAT("พ.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="6" THEN CONCAT("มิ.ย. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="7" THEN CONCAT("ก.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="8" THEN CONCAT("ส.ค. ",RIGHT(YEAR(vstdate)+543,2))
            WHEN MONTH(vstdate)="9" THEN CONCAT("ก.ย. ",RIGHT(YEAR(vstdate)+543,2))
            END AS "month", COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("J128","J159","J188","J189")
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)',[$start_date,$end_date]);
        $diag_m = array_column($diag_month,'month');
        $diag_visit_m = array_column($diag_month,'visit');
        $diag_hn_m = array_column($diag_month,'hn');
        $diag_admit_m = array_column($diag_month,'admit');  
        $diag_refer_m = array_column($diag_month,'refer');  
        
        $diag_year = DB::connection('hosxp')->select('
            SELECT IF(MONTH(vstdate)>9,YEAR(vstdate)+1,YEAR(vstdate)) + 543 AS year_bud,
            COUNT(DISTINCT hn) AS "hn",COUNT(vn) AS "visit",
            SUM(CASE WHEN admit <> "" THEN 1 ELSE 0 END) AS admit,
            SUM(CASE WHEN refer <> "" THEN 1 ELSE 0 END) AS refer
            FROM (SELECT o.vn,o.vstdate,o.hn,o.an AS admit,r.vn AS refer
            FROM ovst o 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN referout r ON r.vn=o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND v.pdx IN ("J128","J159","J188","J189")
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud',[$start_date_y,$end_date]);
        $diag_y = array_column($diag_year,'year_bud');
        $diag_visit_y = array_column($diag_year,'visit');
        $diag_hn_y = array_column($diag_year,'hn');
        $diag_admit_y = array_column($diag_year,'admit');  
        $diag_refer_y = array_column($diag_year,'refer');
    
        $diag_list = DB::connection('hosxp')->select('
            SELECT o.vn,o.vstdate,o.vsttime,o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            v.age_y,CONCAT(p.hipdata_code,"-",p.name) AS pttype,oc.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS dx,
            GROUP_CONCAT(DISTINCT od2.icd10) AS icd9,d.`name` AS dx_doctor,o.an AS admit,CONCAT(h.`name`," [",r.pdx,"]") AS refer,
            v.inc03 AS inc_lab,v.inc12 AS inc_drug
            FROM ovst o 
            LEFT JOIN opdscreen oc ON oc.vn=o.vn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn=o.vn AND od.diagtype NOT IN ("1","2")
            LEFT JOIN ovstdiag od2 ON od2.vn=o.vn AND od2.diagtype = "2"
            LEFT JOIN referout r ON r.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=r.refer_hospcode
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN pttype p ON p.pttype=o.pttype
            LEFT JOIN doctor d ON d.`code`=v.dx_doctor
            WHERE o.vstdate BETWEEN ? AND ?
            AND (v.pdx IN ("J128","J159","J188","J189") 
            OR v.dx0 IN ("J128","J159","J188","J189") 
            OR v.dx1 IN ("J128","J159","J188","J189") 
            OR v.dx2 IN ("J128","J159","J188","J189") 
            OR v.dx3 IN ("J128","J159","J188","J189") 
            OR v.dx4 IN ("J128","J159","J188","J189") 
            OR v.dx5 IN ("J128","J159","J188","J189"))
            GROUP BY o.vn',[$start_date,$end_date]);         

        return view('opd.diag_pneumonia',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

}
