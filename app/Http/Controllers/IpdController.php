<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Session;

class IpdController extends Controller
{
//Check Login
public function __construct()
    {
        $this->middleware('auth');
    }
#################################################################################################################################
//Create wait_doctor_dchsummary
public function wait_doctor_dchsummary(Request $request)
{      
      $start_date = $request->start_date;
      $end_date = $request->end_date;
      if($start_date == '' || $end_date == null)
      {$start_date = Session::get('start_date');}else{$start_date =$request->start_date;}
      if($end_date == '' || $end_date == null)
      {$end_date = Session::get('end_date');}else{$end_date =$request->end_date;}

      $non_dchsummary=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,i.an,iptdiag.icd10,a.diag_text_list,d.`name` AS owner_doctor_name,
            i.dchdate,TIMESTAMPDIFF(day,i.dchdate,DATE(NOW())) AS dch_day,
            CASE WHEN (a.diag_text_list ="" OR a.diag_text_list IS NULL) THEN "รอแพทย์สรุป Chart"
            WHEN (iptdiag.icd10 ="" OR iptdiag.icd10 IS NULL) THEN "รอลงรหัสวินิจฉัยโรค" END AS diag_status
            FROM ipt i
            LEFT JOIN ward w ON w.ward=i.ward        
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
            AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
            GROUP BY i.an
            ORDER BY d.`name`,dch_day DESC');  

      $non_dchsummary_sum=DB::connection('hosxp')->select('
            SELECT d.`name` AS owner_doctor_name,COUNT(i.an) AS total
            FROM ipt i     
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
            AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
            AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
            GROUP BY d.`name` 
            ORDER BY total DESC'); 
      $owner_doctor_name = array_column($non_dchsummary_sum,'owner_doctor_name');
      $owner_doctor_total = array_column($non_dchsummary_sum,'total');

      $request->session()->put('start_date',$start_date);
      $request->session()->put('end_date',$end_date);
      $request->session()->save();
      
      return view('home_detail.ipd_non_dchsummary',compact('non_dchsummary','owner_doctor_name','owner_doctor_total'));        
}
//Create wait_icd_coder
public function wait_icd_coder(Request $request)
    {      
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = Session::get('start_date');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = Session::get('end_date');}else{$end_date =$request->end_date;}

        $sql=DB::connection('hosxp')->select('
                SELECT COUNT(an) AS sum_discharge,
                SUM(CASE WHEN (dx1 IS NULL OR dx1 ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                    OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
                SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                    OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit,SUM(rw) AS sum_rw
                FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
                id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
                id5.audit_diag_text AS dx5_audit,a.pdx,a.rw 
                FROM ipt i
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
                LEFT JOIN an_stat a ON a.an=i.an
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                GROUP BY i.an) AS a'); 
        foreach ($sql as $row){
                $sum_discharge = $row->sum_discharge;
                $sum_wait_dchsummary = $row->sum_wait_dchsummary;
                $sum_wait_icd_coder = $row->sum_wait_icd_coder;
                $sum_dchsummary = $row->sum_dchsummary;
                $sum_dchsummary_audit = $row->sum_dchsummary_audit;
                $sum_rw = $row->sum_rw;
        }

        $data=DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,a.rw       
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                AND (id1.diag_text <> "" OR id1.diag_text IS NOT NULL) AND (a.pdx = "" OR a.pdx IS NULL)                
                GROUP BY i.an'); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->save();
        
        return view('ipd.dchsummary',compact('start_date','end_date','sum_discharge','sum_wait_dchsummary',
            'sum_wait_icd_coder','sum_dchsummary','sum_dchsummary_audit','sum_rw','data'));        
    }

//Create dchsummary
public function dchsummary(Request $request)
    {      
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = Session::get('start_date');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = Session::get('end_date');}else{$end_date =$request->end_date;}

        $sql=DB::connection('hosxp')->select('
                SELECT COUNT(an) AS sum_discharge,
                SUM(CASE WHEN (dx1 IS NULL OR dx1 ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                    OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
                SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                    OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit,SUM(rw) AS sum_rw
                FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
                id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
                id5.audit_diag_text AS dx5_audit,a.pdx,a.rw 
                FROM ipt i
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
                LEFT JOIN an_stat a ON a.an=i.an
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                GROUP BY i.an) AS a'); 
        foreach ($sql as $row){
                $sum_discharge = $row->sum_discharge;
                $sum_wait_dchsummary = $row->sum_wait_dchsummary;
                $sum_wait_icd_coder = $row->sum_wait_icd_coder;
                $sum_dchsummary = $row->sum_dchsummary;
                $sum_dchsummary_audit = $row->sum_dchsummary_audit;
                $sum_rw = $row->sum_rw;
        }

        $data=DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,a.rw       
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                AND (id1.an IS NOT NULL OR id1.an <>"") AND a.pdx <> "" AND a.pdx IS NOT NULL                 
                GROUP BY i.an'); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->save();
        
        return view('ipd.dchsummary',compact('start_date','end_date','sum_discharge','sum_wait_dchsummary',
            'sum_wait_icd_coder','sum_dchsummary','sum_dchsummary_audit','sum_rw','data'));        
    }
//Create dchsummary
public function dchsummary_audit(Request $request)
    {      
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = Session::get('start_date');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = Session::get('end_date');}else{$end_date =$request->end_date;}

        $sql=DB::connection('hosxp')->select('
                SELECT COUNT(an) AS sum_discharge,
                SUM(CASE WHEN (dx1 IS NULL OR dx1 ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                    OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
                SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                    OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit,SUM(rw) AS sum_rw
                FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
                id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
                id5.audit_diag_text AS dx5_audit,a.pdx,a.rw 
                FROM ipt i
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
                LEFT JOIN an_stat a ON a.an=i.an
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                GROUP BY i.an) AS a'); 
        foreach ($sql as $row){
                $sum_discharge = $row->sum_discharge;
                $sum_wait_dchsummary = $row->sum_wait_dchsummary;
                $sum_wait_icd_coder = $row->sum_wait_icd_coder;
                $sum_dchsummary = $row->sum_dchsummary;
                $sum_dchsummary_audit = $row->sum_dchsummary_audit;
                $sum_rw = $row->sum_rw;
        }

        $data=DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,a.rw       
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")                
                AND i.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'"
                AND ((id1.audit_diag_text IS NOT NULL OR id1.audit_diag_text <>"") 
                  OR (id2.audit_diag_text IS NOT NULL OR id2.audit_diag_text <>"")
                  OR (id3.audit_diag_text IS NOT NULL OR id3.audit_diag_text <>"")
                  OR (id4.audit_diag_text IS NOT NULL OR id4.audit_diag_text <>"")
                  OR (id5.audit_diag_text IS NOT NULL OR id5.audit_diag_text <>""))
                GROUP BY i.an'); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->save();
        
        return view('ipd.dchsummary',compact('start_date','end_date','sum_discharge','sum_wait_dchsummary',
            'sum_wait_icd_coder','sum_dchsummary','sum_dchsummary_audit','sum_rw','data'));        
    }

}
