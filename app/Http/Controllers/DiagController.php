<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }

    //Create sepsis
    public function sepsis(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $budget_year_select = DB::select('select LEAVE_YEAR_ID,LEAVE_YEAR_NAME FROM budget_year ORDER BY LEAVE_YEAR_ID DESC LIMIT 7');
        $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year;
            if($budget_year == '' || $budget_year == null)
            {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
        $start_date_y =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year-4)->value('DATE_BEGIN');
        $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
        $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND v.pdx IN ("A419","R651","R572") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)');
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
            WHERE o.vstdate BETWEEN "'.$start_date_y.'" AND "'.$end_date.'"
            AND v.pdx IN ("A419","R651","R572") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud');
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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (v.pdx IN ("A419","R651","R572") 
            OR v.dx0 IN ("A419","R651","R572") 
            OR v.dx1 IN ("A419","R651","R572")  
            OR v.dx2 IN ("A419","R651","R572")  
            OR v.dx3 IN ("A419","R651","R572") 
            OR v.dx4 IN ("A419","R651","R572") 
            OR v.dx5 IN ("A419","R651","R572"))
            GROUP BY o.vn');         

        return view('diag.sepsis',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

    //Create stroke
    public function stroke(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $budget_year_select = DB::select('select LEAVE_YEAR_ID,LEAVE_YEAR_NAME FROM budget_year ORDER BY LEAVE_YEAR_ID DESC LIMIT 7');
        $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year;
            if($budget_year == '' || $budget_year == null)
            {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
        $start_date_y =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year-4)->value('DATE_BEGIN');
        $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
        $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND v.pdx IN ("I64") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)');
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
            WHERE o.vstdate BETWEEN "'.$start_date_y.'" AND "'.$end_date.'"
            AND v.pdx IN ("I64") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud');
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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (v.pdx IN ("I64") 
            OR v.dx0 IN ("I64") 
            OR v.dx1 IN ("I64")  
            OR v.dx2 IN ("I64")  
            OR v.dx3 IN ("I64") 
            OR v.dx4 IN ("I64") 
            OR v.dx5 IN ("I64"))
            GROUP BY o.vn');         

        return view('diag.stroke',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

    //Create stemi
    public function stemi(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $budget_year_select = DB::select('select LEAVE_YEAR_ID,LEAVE_YEAR_NAME FROM budget_year ORDER BY LEAVE_YEAR_ID DESC LIMIT 7');
        $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year;
            if($budget_year == '' || $budget_year == null)
            {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
        $start_date_y =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year-4)->value('DATE_BEGIN');
        $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
        $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND v.pdx IN ("I210","I211","I212","I213") 
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)');
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
            WHERE o.vstdate BETWEEN "'.$start_date_y.'" AND "'.$end_date.'"
            AND v.pdx IN ("I210","I211","I212","I213") 
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud');
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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (v.pdx IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx0 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx1 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx2 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx3 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx4 IN ("I21","I210","I211","I212","I213","I214","I219")
            OR v.dx5 IN ("I21","I210","I211","I212","I213","I214","I219"))
            GROUP BY o.vn');         

        return view('diag.stemi',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

    //Create pneumonia
    public function pneumonia(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $budget_year_select = DB::select('select LEAVE_YEAR_ID,LEAVE_YEAR_NAME FROM budget_year ORDER BY LEAVE_YEAR_ID DESC LIMIT 7');
        $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year;
            if($budget_year == '' || $budget_year == null)
            {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
        $start_date_y =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year-4)->value('DATE_BEGIN');
        $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
        $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND v.pdx IN ("J128","J159","J188","J189")
            GROUP BY o.vn ) AS a
            GROUP BY MONTH(vstdate)
            ORDER BY YEAR(vstdate),MONTH(vstdate)');
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
            WHERE o.vstdate BETWEEN "'.$start_date_y.'" AND "'.$end_date.'"
            AND v.pdx IN ("J128","J159","J188","J189")
            GROUP BY o.vn ) AS a
            GROUP BY year_bud
            ORDER BY year_bud');
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
            WHERE o.vstdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (v.pdx IN ("J128","J159","J188","J189") 
            OR v.dx0 IN ("J128","J159","J188","J189") 
            OR v.dx1 IN ("J128","J159","J188","J189") 
            OR v.dx2 IN ("J128","J159","J188","J189") 
            OR v.dx3 IN ("J128","J159","J188","J189") 
            OR v.dx4 IN ("J128","J159","J188","J189") 
            OR v.dx5 IN ("J128","J159","J188","J189"))
            GROUP BY o.vn');         

        return view('diag.pneumonia',compact('budget_year_select','budget_year','diag_m','diag_visit_m','diag_hn_m','diag_admit_m','diag_refer_m',
            'diag_y','diag_visit_y','diag_hn_y','diag_admit_y','diag_refer_y','diag_list'));            
    }

}
